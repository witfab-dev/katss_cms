<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Please login first";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inline Modal Test</title>
    <link rel="stylesheet" href="admin-style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <h2>Direct Modal Test</h2>
    
    <button class="btn btn-primary" onclick="openModalDirect()">Open Modal Direct</button>
    <button class="btn btn-secondary" onclick="openModalWithFunction()">Open Modal with Function</button>
    
    <div id="testOutput"></div>
    
    <!-- MANAGEMENT TEAM MODAL -->
    <div id="teamModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="teamModalTitle">Add Team Member</h3>
                <button class="modal-close" onclick="closeModalDirect()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="teamForm">
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" id="teamName" required placeholder="Full name">
                    </div>
                    <div class="form-group">
                        <label>Telephone *</label>
                        <input type="tel" id="teamTelephone" required placeholder="Phone number">
                    </div>
                    <div class="form-group">
                        <label>Post/Role *</label>
                        <input type="text" id="teamPost" required placeholder="Position or role">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModalDirect()">Cancel</button>
                <button class="btn btn-primary" onclick="saveTeamMember()">Save Team Member</button>
            </div>
        </div>
    </div>
    
    <script>
        function log(message) {
            document.getElementById('testOutput').innerHTML += '<div>' + message + '</div>';
            console.log(message);
        }
        
        function openModalDirect() {
            log('Opening modal directly...');
            
            const modal = document.getElementById('teamModal');
            if (!modal) {
                log('ERROR: Modal not found');
                return;
            }
            
            log('Modal found: ' + modal.id);
            log('Current classes: ' + modal.className);
            
            // Add open class
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
            
            log('Modal opened. Classes now: ' + modal.className);
            log('Display style: ' + window.getComputedStyle(modal).display);
        }
        
        function closeModalDirect() {
            log('Closing modal...');
            
            const modal = document.getElementById('teamModal');
            if (modal) {
                modal.classList.remove('open');
                document.body.style.overflow = '';
                log('Modal closed');
            }
        }
        
        function openModalWithFunction() {
            log('Testing with function...');
            
            try {
                // Test if the function exists
                if (typeof openTeamModal === 'function') {
                    log('openTeamModal function exists, calling it...');
                    openTeamModal();
                    log('Function called successfully');
                } else {
                    log('openTeamModal function does not exist');
                    
                    // Define it manually
                    log('Defining openTeamModal function...');
                    window.openTeamModal = function(memberId = null) {
                        log('openTeamModal called with memberId: ' + memberId);
                        const modal = document.getElementById('teamModal');
                        const title = document.getElementById('teamModalTitle');
                        
                        if (memberId) {
                            title.textContent = 'Edit Team Member';
                        } else {
                            title.textContent = 'Add Team Member';
                            document.getElementById('teamForm').reset();
                            document.getElementById('teamId').value = '';
                        }
                        
                        if (modal) {
                            modal.classList.add('open');
                            document.body.style.overflow = 'hidden';
                            log('Modal opened via function');
                        }
                    };
                    
                    // Now call it
                    log('Calling newly defined function...');
                    openTeamModal();
                }
            } catch (error) {
                log('ERROR: ' + error.message);
            }
        }
        
        function saveTeamMember() {
            log('Save team member called (test)');
            closeModalDirect();
        }
        
        // Test on load
        window.addEventListener('load', function() {
            log('Page loaded');
            log('Checking for modal...');
            const modal = document.getElementById('teamModal');
            log('Modal found: ' + (modal ? 'YES' : 'NO'));
            
            if (modal) {
                log('Modal CSS classes: ' + modal.className);
                log('Modal computed display: ' + window.getComputedStyle(modal).display);
            }
        });
    </script>
</body>
</html>
