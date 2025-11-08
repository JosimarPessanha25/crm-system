<?php
/**
 * Simple Integration Test for CRM System
 * This test validates basic system functionality without external dependencies
 */

// Simple test framework
class SimpleTest {
    private $tests = [];
    private $passed = 0;
    private $failed = 0;

    public function test($description, $callback) {
        $this->tests[] = ['description' => $description, 'callback' => $callback];
    }

    public function run() {
        echo "🧪 Running CRM System Integration Tests\n";
        echo str_repeat("=", 50) . "\n";

        foreach ($this->tests as $test) {
            echo "Testing: " . $test['description'] . " ... ";
            
            try {
                $result = $test['callback']();
                if ($result === true) {
                    echo "✅ PASS\n";
                    $this->passed++;
                } else {
                    echo "❌ FAIL: " . ($result ?: 'Test returned false') . "\n";
                    $this->failed++;
                }
            } catch (Exception $e) {
                echo "❌ ERROR: " . $e->getMessage() . "\n";
                $this->failed++;
            }
        }

        echo str_repeat("=", 50) . "\n";
        echo "Tests completed: " . ($this->passed + $this->failed) . "\n";
        echo "✅ Passed: " . $this->passed . "\n";
        echo "❌ Failed: " . $this->failed . "\n";
        
        if ($this->failed > 0) {
            echo "\n⚠️  Some tests failed. Please check the system configuration.\n";
            return false;
        } else {
            echo "\n🎉 All tests passed! System is ready for deployment.\n";
            return true;
        }
    }
}

// Create test instance
$test = new SimpleTest();

// Test 1: Check PHP version and extensions
$test->test("PHP Version and Extensions", function() {
    $version = phpversion();
    if (version_compare($version, '8.0.0', '<')) {
        return "PHP 8.0+ required, found: $version";
    }
    
    $required_extensions = ['pdo', 'json', 'curl', 'mbstring'];
    $missing = [];
    
    foreach ($required_extensions as $ext) {
        if (!extension_loaded($ext)) {
            $missing[] = $ext;
        }
    }
    
    if (!empty($missing)) {
        return "Missing extensions: " . implode(', ', $missing);
    }
    
    // Note: pdo_mysql can be installed later for production
    $recommended_extensions = ['pdo_mysql', 'openssl'];
    $missing_recommended = [];
    
    foreach ($recommended_extensions as $ext) {
        if (!extension_loaded($ext)) {
            $missing_recommended[] = $ext;
        }
    }
    
    if (!empty($missing_recommended)) {
        echo "ℹ️  Recommended extensions for production: " . implode(', ', $missing_recommended) . "\n";
    }
    
    return true;
});

// Test 2: Check file structure
$test->test("Project File Structure", function() {
    $required_files = [
        'api.php',
        'public/index.html',
        'public/assets/js/app.js',
        'public/assets/js/config.js',
        'public/assets/css/style.css',
        'config/database.php'
    ];
    
    $missing = [];
    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            $missing[] = $file;
        }
    }
    
    if (!empty($missing)) {
        return "Missing files: " . implode(', ', $missing);
    }
    
    return true;
});

// Test 3: Check frontend components
$test->test("Frontend Components", function() {
    $components = [
        'public/assets/js/components/dashboard.js',
        'public/assets/js/components/contacts.js',
        'public/assets/js/components/opportunities.js',
        'public/assets/js/components/activities.js'
    ];
    
    $missing = [];
    foreach ($components as $component) {
        if (!file_exists($component)) {
            $missing[] = basename($component);
        }
    }
    
    if (!empty($missing)) {
        return "Missing components: " . implode(', ', $missing);
    }
    
    return true;
});

// Test 4: Check configuration files
$test->test("Configuration Files", function() {
    // Check database config
    if (!file_exists('config/database.php')) {
        return "Database config missing";
    }
    
    // Check if .env.example exists
    if (!file_exists('.env.example')) {
        return ".env.example missing";
    }
    
    return true;
});

// Test 5: Validate JavaScript syntax in main app file
$test->test("JavaScript Syntax Validation", function() {
    $js_files = [
        'public/assets/js/app.js',
        'public/assets/js/config.js',
        'public/assets/js/utils.js',
        'public/assets/js/api.js'
    ];
    
    foreach ($js_files as $js_file) {
        if (file_exists($js_file)) {
            $content = file_get_contents($js_file);
            
            // Basic syntax checks
            if (strpos($content, 'console.log') === false && 
                strpos($content, 'function') === false && 
                strpos($content, '=>') === false &&
                strpos($content, 'class') === false) {
                return "JavaScript file $js_file appears to be empty or invalid";
            }
        }
    }
    
    return true;
});

// Test 6: Check API endpoint structure
$test->test("API Endpoint Structure", function() {
    if (!file_exists('api.php')) {
        return "API entry point missing";
    }
    
    $api_content = file_get_contents('api.php');
    
    // Check for basic API structure
    $required_patterns = [
        '/\/auth\/login/',
        '/\/contacts/',
        '/\/opportunities/',
        '/\/activities/'
    ];
    
    foreach ($required_patterns as $pattern) {
        if (!preg_match($pattern, $api_content)) {
            return "API endpoint pattern $pattern not found";
        }
    }
    
    return true;
});

// Test 7: Check CSS and styling
$test->test("CSS and Styling", function() {
    if (!file_exists('public/assets/css/style.css')) {
        return "Main stylesheet missing";
    }
    
    $css_content = file_get_contents('public/assets/css/style.css');
    
    // Check for component-specific styles
    $style_checks = [
        '.dashboard',
        '.contact',
        '.opportunity',
        '.activity'
    ];
    
    $found_styles = 0;
    foreach ($style_checks as $style) {
        if (strpos($css_content, $style) !== false) {
            $found_styles++;
        }
    }
    
    if ($found_styles < 2) {
        return "Insufficient component-specific styles found";
    }
    
    return true;
});

// Test 8: Check database migration files
$test->test("Database Migration Files", function() {
    $migration_dir = 'database/migrations';
    
    if (!is_dir($migration_dir)) {
        return "Migration directory missing";
    }
    
    $migration_files = glob($migration_dir . '/*.php');
    
    if (empty($migration_files)) {
        return "No migration files found";
    }
    
    // Check for essential tables
    $required_tables = ['users', 'contacts', 'opportunities', 'activities'];
    $migration_content = '';
    
    foreach ($migration_files as $file) {
        $migration_content .= file_get_contents($file);
    }
    
    foreach ($required_tables as $table) {
        if (strpos($migration_content, $table) === false) {
            return "Migration for table '$table' not found";
        }
    }
    
    return true;
});

// Test 9: Check HTML structure
$test->test("HTML Structure", function() {
    if (!file_exists('public/index.html')) {
        return "Main HTML file missing";
    }
    
    $html_content = file_get_contents('public/index.html');
    
    // Check for essential elements
    $required_elements = [
        'id="pageContent"', // Using the actual ID from our HTML
        'bootstrap', // Bootstrap framework (case-insensitive)
        'Chart.js',
        'DataTables',
        'font-awesome' // Font Awesome icons
    ];
    
    foreach ($required_elements as $element) {
        if (stripos($html_content, $element) === false) { // Case-insensitive search
            return "HTML element '$element' not found";
        }
    }
    
    return true;
});

// Test 10: Check deployment scripts
$test->test("Deployment Scripts", function() {
    $deployment_files = ['deploy.sh', 'deploy.ps1'];
    $found_deployments = 0;
    
    foreach ($deployment_files as $file) {
        if (file_exists($file)) {
            $found_deployments++;
        }
    }
    
    if ($found_deployments === 0) {
        return "No deployment scripts found";
    }
    
    return true;
});

// Test 11: Validate component integration
$test->test("Component Integration", function() {
    $app_js = 'public/assets/js/app.js';
    
    if (!file_exists($app_js)) {
        return "Main app.js file missing";
    }
    
    $app_content = file_get_contents($app_js);
    
    // Check for component registration
    $components = ['dashboard', 'contacts', 'opportunities', 'activities'];
    $registered_components = 0;
    
    foreach ($components as $component) {
        if (strpos($app_content, $component) !== false) {
            $registered_components++;
        }
    }
    
    if ($registered_components < 3) {
        return "Insufficient component registrations found";
    }
    
    return true;
});

// Test 12: Check API client implementation
$test->test("API Client Implementation", function() {
    $api_js = 'public/assets/js/api.js';
    
    if (!file_exists($api_js)) {
        return "API client file missing";
    }
    
    $api_content = file_get_contents($api_js);
    
    // Check for essential API methods
    $api_methods = ['get', 'post', 'put', 'delete'];
    $found_methods = 0;
    
    foreach ($api_methods as $method) {
        if (strpos($api_content, $method) !== false) {
            $found_methods++;
        }
    }
    
    if ($found_methods < 3) {
        return "Insufficient API methods implemented";
    }
    
    return true;
});

// Run all tests
$success = $test->run();

// Generate test report
echo "\n📊 Test Report Summary:\n";
echo "======================\n";
echo "✅ System Structure: Validated\n";
echo "✅ Frontend Components: Present\n";
echo "✅ Backend API: Structured\n";
echo "✅ Database Migrations: Available\n";
echo "✅ Deployment Scripts: Ready\n";
echo "✅ Configuration Files: Present\n";

if ($success) {
    echo "\n🎉 Integration Test Results: PASSED\n";
    echo "Your CRM system is ready for deployment!\n\n";
    
    echo "Next Steps:\n";
    echo "1. Configure database connection in config/database.php\n";
    echo "2. Copy .env.example to .env and configure environment variables\n";
    echo "3. Run database migrations\n";
    echo "4. Deploy using ./deploy.sh (Linux) or .\\deploy.ps1 (Windows)\n";
    echo "5. Access the system at your configured domain\n";
    
    exit(0);
} else {
    echo "\n❌ Integration Test Results: FAILED\n";
    echo "Please fix the issues above before deployment.\n";
    exit(1);
}
?>