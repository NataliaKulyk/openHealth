<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DivisionScrollToErrorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the JavaScript scrollToElement function exists in the app.js file
     */
    public function test_scroll_to_element_function_exists(): void
    {
        $jsContent = file_get_contents(resource_path('js/app.js'));
        
        // Check that the JavaScript file contains the scrollToElement function
        $this->assertStringContainsString('function scrollToElement', $jsContent);
    }

    /**
     * Test that the division-form-failed event listener exists in the app.js file
     */
    public function test_division_form_failed_event_listener_exists(): void
    {
        $jsContent = file_get_contents(resource_path('js/app.js'));
        
        // Check that the JavaScript file contains the division-form-failed event listener
        $this->assertStringContainsString("Livewire.on('division-form-failed'", $jsContent);
    }

    /**
     * Test that the scrollToElement function is called with correct selector
     */
    public function test_scroll_to_element_called_with_correct_selector(): void
    {
        $jsContent = file_get_contents(resource_path('js/app.js'));
        
        // Check that the scrollToElement function is called with the correct selector
        $this->assertStringContainsString("scrollToElement('.input-error, .select-error')", $jsContent);
    }
}
