    </main>
</div>

<!-- User Settings Modal -->
<div class="admin-modal" id="userSettingsModal">
    <div class="admin-modal-overlay" data-close-modal></div>
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h2 class="admin-modal-title">Profile Settings</h2>
            <button class="admin-modal-close" type="button" data-close-modal aria-label="Close">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M18 6L6 18M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="profileForm" method="post" action="/admin/save-profile.php" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <div class="admin-modal-body">
                <div class="admin-settings-section">
                    <h3 class="admin-settings-label">Profile Picture</h3>
                    <div class="admin-avatar-upload">
                        <?php 
                        $currentAvatar = dh_site_setting('admin.profile_picture', '');
                        $currentAvatarUrl = '';
                        if ($currentAvatar !== '') {
                            $currentAvatarPath = __DIR__ . '/../..' . $currentAvatar;
                            if (is_file($currentAvatarPath)) {
                                $currentAvatarUrl = $currentAvatar . '?v=' . filemtime($currentAvatarPath);
                            }
                        }
                        $userInitial = strtoupper(substr($_SESSION['admin_email'] ?? 'A', 0, 1));
                        ?>
                        <div class="admin-avatar-preview" id="avatarPreview" <?php if ($currentAvatarUrl !== ''): ?>style="background-image: url('<?php echo htmlspecialchars($currentAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>'); background-size: cover; background-position: center;"<?php endif; ?>>
                            <?php echo $currentAvatarUrl !== '' ? '' : $userInitial; ?>
                        </div>
                        <input type="file" id="avatarInput" name="profile_picture" accept="image/png,image/jpeg,image/jpg,image/webp" hidden>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button type="button" class="admin-btn admin-btn-secondary" onclick="document.getElementById('avatarInput').click()">
                                Choose Image
                            </button>
                            <?php if ($currentAvatarUrl !== ''): ?>
                                <button type="submit" name="action" value="remove_avatar" class="admin-btn admin-btn-danger">Remove</button>
                            <?php endif; ?>
                        </div>
                        <p class="admin-hint" style="margin-top: 8px;">JPG, PNG or WebP. Max 1MB.</p>
                    </div>
                </div>
                <div class="admin-settings-section">
                    <h3 class="admin-settings-label">Account Info</h3>
                    <div class="admin-info-item">
                        <span class="admin-info-label">Email</span>
                        <span class="admin-info-value"><?php echo htmlspecialchars($_SESSION['admin_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            </div>
            <div style="padding: 0 24px 24px; display: flex; gap: 12px; justify-content: space-between;">
                <a href="/admin/logout.php" class="admin-btn admin-btn-danger">Logout</a>
                <button type="submit" name="action" value="save_avatar" class="admin-btn admin-btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var shell = document.querySelector('.admin-shell');
    var toggle = document.querySelector('[data-sidebar-toggle]');
    if (!shell || !toggle) {
        return;
    }
    
    var isMobile = function () {
        return window.innerWidth <= 768;
    };
    
    // Handle click to toggle sidebar
    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        if (isMobile()) {
            // On mobile: toggle overlay state
            shell.classList.toggle('is-expanded');
        } else {
            // On desktop: toggle collapse to icon-only view
            shell.classList.toggle('is-collapsed');
        }
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function (e) {
        if (isMobile() && shell.classList.contains('is-expanded')) {
            var sidebar = document.querySelector('.admin-sidebar');
            if (sidebar && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                shell.classList.remove('is-expanded');
            }
        }
    });
    
    // Handle window resize to adapt behavior
    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (!isMobile()) {
                // On resize to desktop, clear mobile overlay state
                shell.classList.remove('is-expanded');
            } else {
                // On resize to mobile, clear desktop collapse state
                shell.classList.remove('is-collapsed');
            }
        }, 250);
    });
    
    // User menu interactions
    var userButton = document.querySelector('[data-user-menu]');
    var userModal = document.getElementById('userSettingsModal');
    if (userButton && userModal) {
        userButton.addEventListener('click', function (e) {
            e.stopPropagation();
            userModal.classList.add('is-open');
        });
    }
    
    // Modal close handlers
    var closeButtons = document.querySelectorAll('[data-close-modal]');
    if (userModal) {
        closeButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                userModal.classList.remove('is-open');
            });
        });
        
        document.addEventListener('click', function (e) {
            if (userModal.classList.contains('is-open') && e.target === userModal.querySelector('.admin-modal-overlay')) {
                userModal.classList.remove('is-open');
            }
        });
        
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && userModal.classList.contains('is-open')) {
                userModal.classList.remove('is-open');
            }
        });
    }
    
    // Avatar upload preview
    var avatarInput = document.getElementById('avatarInput');
    var avatarPreview = document.getElementById('avatarPreview');
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function (e) {
            var file = e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function (event) {
                    avatarPreview.style.backgroundImage = 'url(' + event.target.result + ')';
                    avatarPreview.style.backgroundSize = 'cover';
                    avatarPreview.style.backgroundPosition = 'center';
                    avatarPreview.textContent = '';
                };
                reader.readAsDataURL(file);
            }
        });
    }
})();
</script>
</body>
</html>
