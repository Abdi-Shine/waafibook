<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Screen Locked - Waafibook</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .shake {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
    </style>
</head>
<body class="auth-page-bg">
    <div class="auth-container max-w-[450px] w-full animate-fadeIn">
        <div class="auth-card" id="lockCard">
            <!-- Header -->
            <div class="auth-header-gradient auth-header-border p-5 pb-8 text-center text-white">
                <div class="auth-logo flex justify-center mb-4">
                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-xl overflow-hidden">
                        <img src="{{ asset('upload/horntechlogo/horntech_logo.jpg') }}" class="w-full h-full object-contain" alt="Waafibook Logo">
                    </div>
                </div>
                <h1 class="auth-title">Waafibook</h1>
            </div>
            
            <div class="auth-body p-5 pt-0">
                <!-- User Avatar -->
                
                <!-- Time Display -->
                <div class="time-display">
                    <div class="current-time" id="currentTime">00:00:00</div>
                    <div class="current-date" id="currentDate">Loading...</div>
                </div>

                <!-- Lock Screen Form -->
                <form class="auth-form" id="unlockForm">
                    @csrf
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group relative">
                            <i class="bi bi-lock input-icon"></i>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="password" 
                                name="password"
                                placeholder="••••••••"
                                required
                                autofocus>
                            <button type="button" class="password-toggle" id="passwordToggle">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div id="capsLockWarning" class="text-accent text-[10px] font-bold uppercase mt-2 hidden">
                            <i class="bi bi-exclamation-triangle-fill"></i> Caps Lock is ON
                        </div>
                    </div>

                    <button type="submit" class="btn-auth" id="unlockBtn">
                        <i class="bi bi-unlock-fill me-2"></i>Unlock session
                    </button>
                </form>
            </div>
            
            <!-- Footer -->
            <div class="auth-footer py-4 px-5 text-center border-t border-gray-50">
                 <p class="text-muted m-0 mt-4" style="font-size: 0.75rem;">
                    &copy; {{ date('Y') }} Waafibook. All rights reserved.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Update Time and Date
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('currentTime').textContent = `${hours}:${minutes}:${seconds}`;
            
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', options);
        }
        
        updateTime();
        setInterval(updateTime, 1000);
        
        // Password Toggle
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('passwordToggle');
        
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
        
        // Caps Lock Detection
        passwordInput.addEventListener('keyup', function(event) {
            const capsLockWarning = document.getElementById('capsLockWarning');
            if (event.getModifierState('CapsLock')) {
                capsLockWarning.classList.remove('hidden');
            } else {
                capsLockWarning.classList.add('hidden');
            }
        });
        
        // Handle Form Submission
        const unlockForm = document.getElementById('unlockForm');
        const unlockBtn = document.getElementById('unlockBtn');
        const lockCard = document.getElementById('lockCard');
        
        unlockForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            unlockBtn.disabled = true;
            unlockBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Validating...';
            
            fetch("{{ route('unlock') }}", { 
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Authenticated!',
                        text: 'Welcome back!',
                        showConfirmButton: false,
                        timer: 1000,
                        timerProgressBar: true
                    }).then(() => {
                        window.location.href = "{{ route('dashboard') }}";
                    });
                } else {
                    unlockBtn.disabled = false;
                    unlockBtn.innerHTML = '<i class="bi bi-unlock-fill me-2"></i>Unlock session';
                    
                    // Shake effect
                    lockCard.classList.add('shake');
                    setTimeout(() => lockCard.classList.remove('shake'), 500);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Password',
                        text: data.message || 'The password you entered is incorrect.',
                        confirmButtonColor: '#004161'
                    });
                    
                    passwordInput.value = '';
                    passwordInput.focus();
                }
            })
            .catch(error => {
                unlockBtn.disabled = false;
                unlockBtn.innerHTML = '<i class="bi bi-unlock-fill me-2"></i>Unlock session';
                console.error('Error:', error);
                
                // For demonstration, if route is not found, show appropriate message
                Swal.fire({
                    icon: 'warning',
                    title: 'Authentication Pending',
                    text: 'Unlock route (/unlock) is not configured yet.',
                    confirmButtonColor: '#004161'
                });
            });
        });

        // Focus management
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'l') {
                e.preventDefault();
                passwordInput.focus();
            }
        });
    </script>
</body>
</html>
