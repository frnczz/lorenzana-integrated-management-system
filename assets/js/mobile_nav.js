/**
 * Mobile Navigation Dropdown Handler
 * Handles the module select dropdown on mobile devices
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile Navigation Select Handler
    const mobileNavSelect = document.getElementById('mobileNavSelect');
    
    if (mobileNavSelect) {
        mobileNavSelect.addEventListener('change', function(e) {
            if (this.value) {
                // Don't navigate on logout, let form handle it
                if (this.value === 'logout.php') {
                    window.location.href = this.value;
                } else {
                    // Navigate to selected page
                    window.location.href = this.value;
                }
                // Reset dropdown after selection
                this.value = '';
            }
        });
        
        // Handle touch events for better mobile responsiveness
        mobileNavSelect.addEventListener('touchstart', function() {
            this.focus();
        });
    }
});
