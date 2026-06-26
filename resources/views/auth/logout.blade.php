<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - Waafibook</title>
    
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Global CSS (Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <div class="lock-screen-container">
        <div class="lock-screen-card" id="logoutCard">
            <!-- Header -->
            <div class="lock-screen-header">
                <div class="lock-screen-logo">
                    <svg viewBox="0 0 24 24">
                        <path d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 11 8.76l1-1.36 1 1.36L15.38 12 17 10.83 14.92 8H20v6z"/>
                    </svg>
                </div>
                <h1 class="lock-screen-title">Waafibook</h1>
            </div>
            
            <!-- Logout Success Body -->
            <div class="lock-screen-body">
                <div class="success-checkmark">
                    <div class="check-icon">
                        <span class="icon-line line-tip"></span>
                        <span class="icon-line line-long"></span>
                        <div class="icon-circle"></div>
                    </div>
                </div>
                
                <h2 class="user-name">Successfully Logged Out</h2>
                <p class="user-role">Your session has been securely terminated</p>
                
                <div class="session-info">
                    <div class="session-info-item">
                        <i class="bi bi-shield-check"></i>
                        <span>Session cleared</span>
                    </div>
                    <div class="session-info-item">
                        <i class="bi bi-check-circle"></i>
                        <span>Data secured</span>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-400 text-[13px] font-medium">
                        Redirecting to login in <span id="countdown" class="text-primary font-black">5</span> seconds...
                    </p>
                    <a href="{{ route('login') }}" class="btn-unlock">
                        <i class="bi bi-box-arrow-in-right text-lg"></i>
                        <span>Return to Login</span>
                    </a>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="p-6 bg-gray-50/50 border-t border-gray-100/50 text-center">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest leading-none">
                    © {{ date('Y') }} Waafibook • Core Engine v2.4
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Countdown and Auto-redirect
        let countdown = 5;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            countdown--;
            if (countdownElement) countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = "{{ route('login') }}";
            }
        }, 1000);
        
        // Simple entrance animation
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.getElementById('logoutCard');
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            }
        });
    </script>
</body>
</html>
