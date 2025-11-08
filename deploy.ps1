# CRM System Windows Deployment Script
# PowerShell script to help deploy the CRM system on Windows/IIS

param(
    [Parameter()]
    [string]$Command = "deploy",
    
    [Parameter()]
    [string]$SiteName = "CRM-System",
    
    [Parameter()]
    [string]$ProjectPath = "C:\inetpub\wwwroot\crm-system",
    
    [Parameter()]
    [string]$BackupPath = "C:\Backups\crm-system"
)

# Configuration
$ErrorActionPreference = "Stop"

# Colors for output
$Colors = @{
    Info = "Cyan"
    Success = "Green" 
    Warning = "Yellow"
    Error = "Red"
}

# Helper Functions
function Write-Status {
    param([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor $Colors.Info
}

function Write-Success {
    param([string]$Message)
    Write-Host "[SUCCESS] $Message" -ForegroundColor $Colors.Success
}

function Write-Warning {
    param([string]$Message)
    Write-Host "[WARNING] $Message" -ForegroundColor $Colors.Warning
}

function Write-Error {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor $Colors.Error
}

function Test-Administrator {
    $currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Test-Requirements {
    Write-Status "Checking system requirements..."
    
    # Check if running as Administrator
    if (-not (Test-Administrator)) {
        Write-Error "This script must be run as Administrator"
        exit 1
    }
    
    # Check IIS
    $iisFeature = Get-WindowsOptionalFeature -Online -FeatureName "IIS-WebServerRole"
    if ($iisFeature.State -ne "Enabled") {
        Write-Error "IIS is not installed. Please install IIS first."
        exit 1
    }
    
    Write-Success "IIS is available"
    
    # Check PHP
    try {
        $phpVersion = php -r "echo PHP_VERSION;" 2>$null
        if ($phpVersion) {
            Write-Success "PHP version $phpVersion is available"
            
            # Check PHP version
            $majorVersion = [int]($phpVersion.Split('.')[0])
            if ($majorVersion -lt 8) {
                Write-Error "PHP 8.0 or higher is required. Current version: $phpVersion"
                exit 1
            }
        }
    }
    catch {
        Write-Error "PHP is not installed or not in PATH"
        exit 1
    }
    
    # Check required PHP extensions
    $requiredExtensions = @("pdo", "pdo_mysql", "json", "curl", "mbstring", "openssl")
    foreach ($ext in $requiredExtensions) {
        $extensions = php -m 2>$null
        if ($extensions -notcontains $ext) {
            Write-Error "Required PHP extension missing: $ext"
            exit 1
        }
    }
    
    Write-Success "All required PHP extensions are available"
    
    # Check Composer
    try {
        composer --version 2>$null | Out-Null
        Write-Success "Composer is available"
    }
    catch {
        Write-Error "Composer is not installed"
        exit 1
    }
}

function New-ProjectDirectories {
    Write-Status "Creating project directories..."
    
    $directories = @(
        $ProjectPath,
        "$ProjectPath\logs",
        "$ProjectPath\uploads",
        "$ProjectPath\cache",
        $BackupPath
    )
    
    foreach ($dir in $directories) {
        if (-not (Test-Path $dir)) {
            New-Item -ItemType Directory -Path $dir -Force | Out-Null
            Write-Success "Created directory: $dir"
        }
    }
}

function Backup-Existing {
    if ((Test-Path $ProjectPath) -and (Get-ChildItem $ProjectPath -ErrorAction SilentlyContinue)) {
        Write-Status "Creating backup of existing installation..."
        
        $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
        $backupName = "crm-backup-$timestamp.zip"
        $backupFile = Join-Path $BackupPath $backupName
        
        try {
            Compress-Archive -Path "$ProjectPath\*" -DestinationPath $backupFile -Force
            Write-Success "Backup created: $backupFile"
        }
        catch {
            Write-Warning "Backup creation failed: $_"
        }
    }
}

function Copy-ApplicationFiles {
    Write-Status "Deploying application files..."
    
    # Get current directory
    $sourceDir = Get-Location
    
    # Copy files excluding certain directories
    $excludePatterns = @("*.git*", "node_modules", "tests", "*.log", "deploy.*")
    
    Get-ChildItem -Path $sourceDir -Recurse | Where-Object {
        $exclude = $false
        foreach ($pattern in $excludePatterns) {
            if ($_.FullName -like "*$pattern*") {
                $exclude = $true
                break
            }
        }
        -not $exclude
    } | ForEach-Object {
        $relativePath = $_.FullName.Substring($sourceDir.Path.Length + 1)
        $destinationPath = Join-Path $ProjectPath $relativePath
        
        if ($_.PSIsContainer) {
            if (-not (Test-Path $destinationPath)) {
                New-Item -ItemType Directory -Path $destinationPath -Force | Out-Null
            }
        }
        else {
            $destinationDir = Split-Path $destinationPath -Parent
            if (-not (Test-Path $destinationDir)) {
                New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
            }
            Copy-Item $_.FullName $destinationPath -Force
        }
    }
    
    Write-Success "Application files deployed"
}

function Install-Dependencies {
    Write-Status "Installing PHP dependencies..."
    
    Push-Location $ProjectPath
    
    try {
        & composer install --no-dev --optimize-autoloader --no-interaction
        Write-Success "Dependencies installed"
    }
    catch {
        Write-Error "Failed to install dependencies: $_"
    }
    finally {
        Pop-Location
    }
}

function Set-Permissions {
    Write-Status "Configuring file permissions..."
    
    # Set permissions for writable directories
    $writableDirectories = @(
        "$ProjectPath\logs",
        "$ProjectPath\uploads", 
        "$ProjectPath\cache"
    )
    
    foreach ($dir in $writableDirectories) {
        if (Test-Path $dir) {
            # Grant IIS_IUSRS modify permissions
            $acl = Get-Acl $dir
            $accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
                "IIS_IUSRS", "Modify", "ContainerInherit,ObjectInherit", "None", "Allow"
            )
            $acl.SetAccessRule($accessRule)
            Set-Acl $dir $acl
            Write-Success "Set permissions for: $dir"
        }
    }
}

function Configure-IIS {
    Write-Status "Configuring IIS..."
    
    Import-Module WebAdministration -ErrorAction SilentlyContinue
    
    # Create application pool
    $appPoolName = "$SiteName-AppPool"
    
    if (Get-IISAppPool -Name $appPoolName -ErrorAction SilentlyContinue) {
        Remove-WebAppPool -Name $appPoolName
    }
    
    New-WebAppPool -Name $appPoolName -Force
    Set-ItemProperty -Path "IIS:\AppPools\$appPoolName" -Name "processModel.identityType" -Value "ApplicationPoolIdentity"
    Set-ItemProperty -Path "IIS:\AppPools\$appPoolName" -Name "managedRuntimeVersion" -Value ""
    
    Write-Success "Created application pool: $appPoolName"
    
    # Create website
    if (Get-Website -Name $SiteName -ErrorAction SilentlyContinue) {
        Remove-Website -Name $SiteName
    }
    
    New-Website -Name $SiteName -PhysicalPath "$ProjectPath\public" -ApplicationPool $appPoolName -Port 80
    
    Write-Success "Created website: $SiteName"
    
    # Configure URL Rewrite (if module is available)
    $rewriteConfig = @"
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="Frontend Routes" stopProcessing="true">
                    <match url="^(?!api/).*" />
                    <conditions>
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
                    </conditions>
                    <action type="Rewrite" url="/index.html" />
                </rule>
                <rule name="API Routes" stopProcessing="true">
                    <match url="^api/(.*)" />
                    <action type="Rewrite" url="/api.php" />
                </rule>
            </rules>
        </rewrite>
        <defaultDocument>
            <files>
                <add value="index.html" />
            </files>
        </defaultDocument>
    </system.webServer>
</configuration>
"@
    
    $webConfigPath = "$ProjectPath\public\web.config"
    $rewriteConfig | Out-File -FilePath $webConfigPath -Encoding UTF8
    
    Write-Success "IIS configuration completed"
}

function Initialize-Database {
    Write-Status "Initializing database..."
    
    Push-Location $ProjectPath
    
    try {
        # Run migrations if available
        $migrationScript = "database\migrations\migrate.php"
        if (Test-Path $migrationScript) {
            Write-Status "Running database migrations..."
            & php $migrationScript
            Write-Success "Database migrations completed"
        }
        else {
            Write-Warning "Migration script not found. Please run migrations manually."
        }
        
        # Seed database if requested
        $seedScript = "database\seeds\seed.php"
        if (Test-Path $seedScript) {
            $response = Read-Host "Do you want to seed the database with initial data? (y/N)"
            if ($response -eq 'y' -or $response -eq 'Y') {
                & php $seedScript
                Write-Success "Database seeded with initial data"
            }
        }
    }
    catch {
        Write-Error "Database initialization failed: $_"
    }
    finally {
        Pop-Location
    }
}

function Test-Deployment {
    Write-Status "Running post-deployment tests..."
    
    Push-Location $ProjectPath
    
    try {
        # Run API tests if available
        $apiTestScript = "tests\api-test.php"
        if (Test-Path $apiTestScript) {
            Write-Status "Running API integration tests..."
            & php $apiTestScript "http://localhost"
            
            if ($LASTEXITCODE -eq 0) {
                Write-Success "API tests passed"
            }
            else {
                Write-Warning "API tests failed. Please check the configuration."
            }
        }
        
        # Test web server
        try {
            $response = Invoke-WebRequest -Uri "http://localhost" -UseBasicParsing -TimeoutSec 10
            if ($response.StatusCode -eq 200) {
                Write-Success "Web server is responding correctly"
            }
            else {
                Write-Warning "Web server test failed (HTTP $($response.StatusCode))"
            }
        }
        catch {
            Write-Warning "Web server test failed: $_"
        }
    }
    finally {
        Pop-Location
    }
}

function Show-Summary {
    Write-Success "Deployment completed successfully!"
    Write-Host ""
    Write-Host "==========================================" -ForegroundColor White
    Write-Host "  CRM System Deployment Summary" -ForegroundColor White
    Write-Host "==========================================" -ForegroundColor White
    Write-Host "Project Path: $ProjectPath" -ForegroundColor Gray
    Write-Host "Site Name: $SiteName" -ForegroundColor Gray
    Write-Host "Application Pool: $SiteName-AppPool" -ForegroundColor Gray
    Write-Host "Backup Path: $BackupPath" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Next Steps:" -ForegroundColor White
    Write-Host "1. Configure database settings in config\database.php" -ForegroundColor Gray
    Write-Host "2. Update site bindings for your domain" -ForegroundColor Gray
    Write-Host "3. Install SSL certificate for production" -ForegroundColor Gray
    Write-Host "4. Configure email settings" -ForegroundColor Gray
    Write-Host "5. Set up regular backups" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Default Login:" -ForegroundColor White
    Write-Host "Email: admin@crm.com" -ForegroundColor Gray
    Write-Host "Password: admin123" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Please change the default password after first login!" -ForegroundColor Yellow
    Write-Host "==========================================" -ForegroundColor White
}

# Main Functions
function Invoke-Deploy {
    Write-Status "Starting CRM System deployment..."
    
    Test-Requirements
    New-ProjectDirectories
    Backup-Existing
    Copy-ApplicationFiles
    Install-Dependencies
    Set-Permissions
    Configure-IIS
    Initialize-Database
    Test-Deployment
    Show-Summary
}

function Invoke-Backup {
    Backup-Existing
}

function Invoke-Test {
    Test-Deployment
}

function Invoke-Permissions {
    Set-Permissions
}

function Show-Help {
    Write-Host "CRM System Windows Deployment Script" -ForegroundColor White
    Write-Host ""
    Write-Host "Usage:" -ForegroundColor White
    Write-Host "  .\deploy.ps1 [Command] [Parameters]" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Commands:" -ForegroundColor White
    Write-Host "  deploy      - Full deployment (default)" -ForegroundColor Gray
    Write-Host "  backup      - Create backup only" -ForegroundColor Gray
    Write-Host "  test        - Run tests only" -ForegroundColor Gray
    Write-Host "  permissions - Fix permissions only" -ForegroundColor Gray
    Write-Host "  help        - Show this help" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Parameters:" -ForegroundColor White
    Write-Host "  -SiteName    - IIS site name (default: CRM-System)" -ForegroundColor Gray
    Write-Host "  -ProjectPath - Project installation path" -ForegroundColor Gray
    Write-Host "  -BackupPath  - Backup directory path" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Examples:" -ForegroundColor White
    Write-Host "  .\deploy.ps1" -ForegroundColor Gray
    Write-Host "  .\deploy.ps1 -Command backup" -ForegroundColor Gray
    Write-Host "  .\deploy.ps1 -SiteName 'My-CRM' -ProjectPath 'D:\websites\crm'" -ForegroundColor Gray
}

# Main execution
try {
    switch ($Command.ToLower()) {
        "deploy" { Invoke-Deploy }
        "backup" { Invoke-Backup }
        "test" { Invoke-Test }
        "permissions" { Invoke-Permissions }
        "help" { Show-Help }
        default {
            Write-Error "Unknown command: $Command"
            Write-Host "Use '.\deploy.ps1 help' for available commands" -ForegroundColor Gray
            exit 1
        }
    }
}
catch {
    Write-Error "Deployment failed: $_"
    exit 1
}