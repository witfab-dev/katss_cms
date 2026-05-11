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
    <title>Debug Modal</title>
    <link rel="stylesheet" href="admin-style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <h2>Modal Debug Test</h2>
    
    <button class="btn btn-primary" onclick="testModal()">Test Modal</button>
    <button class="btn btn-secondary" onclick="testConsole()">Test Console</button>
    
    <div id="debugOutput"></div>
    
    <!-- MANAGEMENT TEAM MODAL -->
    <div id="teamModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="teamModalTitle">Add Team Member</h3>
                <button class="modal-close" onclick="closeTeamModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="teamForm">
                    <input type="hidden" id="teamId">
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
                <button class="btn btn-secondary" onclick="closeTeamModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveTeamMember()">Save Team Member</button>
            </div>
        </div>
    </div>
    
    <script>
        function debugLog(message) {
            document.getElementById('debugOutput').innerHTML += '<p>' + message + '</p>';
            console.log(message);
        }
        
        function testModal() {
            debugLog('Testing modal...');
            
            const modal = document.getElementById('teamModal');
            debugLog('Modal element found: ' + (modal ? 'YES' : 'NO'));
            
            if (modal) {
                debugLog('Modal classes: ' + modal.className);
                debugLog('Modal style display: ' + modal.style.display);
                
                // Try to open modal
                modal.classList.add('open');
                document.body.style.overflow = 'hidden';
                debugLog('Modal should now be open');
            }
        }
        
        function testConsole() {
            debugLog('Checking if functions exist...');
            
            if (typeof openTeamModal === 'function') {
                debugLog('✅ openTeamModal function exists');
            } else {
                debugLog('❌ openTeamModal function does NOT exist');
            }
            
            if (typeof closeTeamModal === 'function') {
                debugLog('✅ closeTeamModal function exists');
            } else {
                debugLog('❌ closeTeamModal function does NOT exist');
            }
            
            // Test calling the function
            try {
                debugLog('Calling openTeamModal()...');
                openTeamModal();
                debugLog('✅ Function called successfully');
            } catch (error) {
                debugLog('❌ Error calling function: ' + error.message);
            }
        }
        
        function openTeamModal(memberId = null) {
            debugLog('openTeamModal called with memberId: ' + memberId);
            
            const modal = document.getElementById('teamModal');
            const title = document.getElementById('teamModalTitle');
            
            debugLog('Modal element: ' + (modal ? 'found' : 'NOT found'));
            debugLog('Title element: ' + (title ? 'found' : 'NOT found'));
            
            if (memberId) {
                title.textContent = 'Edit Team Member';
                // loadTeamMember(memberId);
            } else {
                title.textContent = 'Add Team Member';
                document.getElementById('teamForm').reset();
                document.getElementById('teamId').value = '';
            }
            
            if (modal) {
                modal.classList.add('open');
                document.body.style.overflow = 'hidden';
                debugLog('Modal opened successfully');
            } else {
                debugLog('ERROR: Modal element not found');
            }
        }
        
        function closeTeamModal() {
            debugLog('closeTeamModal called');
            const modal = document.getElementById('teamModal');
            if (modal) {
                modal.classList.remove('open');
                document.body.style.overflow = '';
                debugLog('Modal closed');
            }
        }
        
        function saveTeamMember() {
            debugLog('saveTeamMember called - just for testing');
            closeTeamModal();
        }
    </script>
</body>
</html>
