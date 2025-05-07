// Smooth scrolling for banner CTA and CTA section
document.addEventListener('DOMContentLoaded', function() {
    // Get all elements that need smooth scrolling
    const bannerCta = document.querySelector('.banner__cta');
    const ctaLink = document.querySelector('.cta__link');
    const preRegisterSection = document.querySelector('.pre-register');

    // Function to handle smooth scrolling
    function smoothScroll(target) {
        target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    // Add click event listeners for smooth scrolling
    if (bannerCta) {
        bannerCta.addEventListener('click', function(e) {
            e.preventDefault();
            smoothScroll(preRegisterSection);
        });
    }

    if (ctaLink) {
        ctaLink.addEventListener('click', function(e) {
            e.preventDefault();
            smoothScroll(preRegisterSection);
        });
    }

    // Interest selection functionality with icon switching
    const interestOptions = document.querySelectorAll('.pre-register__option');
    
    // Icon paths configuration
    const iconConfig = {
        megaphone: {
            active: 'assets/images/majesticons_megaphone.svg',
            inactive: 'assets/images/majesticons_megaphone-gray.svg'
        },
        search: {
            active: 'assets/images/lucide_search.svg',
            inactive: 'assets/images/lucide_search-gray.svg'
        }
    };

    // Function to update icon
    function updateIcon(option, isActive) {
        const icon = option.querySelector('.pre-register__option-icon');
        const currentSrc = icon.src;
        const isMegaphone = currentSrc.includes('megaphone');
        
        icon.src = isActive 
            ? (isMegaphone ? iconConfig.megaphone.active : iconConfig.search.active)
            : (isMegaphone ? iconConfig.megaphone.inactive : iconConfig.search.inactive);
    }
    
    interestOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Update all options
            interestOptions.forEach(opt => {
                const isActive = opt === this;
                opt.classList.toggle('pre-register__option--active', isActive);
                updateIcon(opt, isActive);
            });
        });
    });

    // Form handling
    const form = document.querySelector('.pre-register__form');
    const googleBtn = document.querySelector('.pre-register__google');

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const activeOption = document.querySelector('.pre-register__option--active');
            formData.append('interest', activeOption.textContent.includes('anunciar') ? 'provider' : 'client');

            // Show loading state
            Swal.fire({
                title: 'Processando...',
                text: 'Estamos realizando seu pré-cadastro',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: 'process.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        // Se a resposta já for um objeto, use diretamente
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Pré-cadastro realizado!',
                                text: data.message,
                                confirmButtonText: 'Ótimo!',
                                confirmButtonColor: '#405FF2',
                                background: '#fff',
                                customClass: {
                                    popup: 'animated fadeInDown'
                                },
                                showClass: {
                                    popup: 'animate__animated animate__fadeInDown'
                                },
                                hideClass: {
                                    popup: 'animate__animated animate__fadeOutUp'
                                }
                            }).then(() => {
                                form.reset();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: data.message,
                                confirmButtonText: 'Tentar novamente',
                                confirmButtonColor: '#405FF2',
                                background: '#fff',
                                customClass: {
                                    popup: 'animated fadeInDown'
                                }
                            });
                        }
                    } catch (error) {
                        console.error('Erro ao processar resposta:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: 'Ocorreu um erro ao processar a resposta do servidor.',
                            confirmButtonText: 'Tentar novamente',
                            confirmButtonColor: '#405FF2',
                            background: '#fff'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisição:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Ocorreu um erro ao processar seu cadastro. Por favor, tente novamente.',
                        confirmButtonText: 'Tentar novamente',
                        confirmButtonColor: '#405FF2',
                        background: '#fff',
                        customClass: {
                            popup: 'animated fadeInDown'
                        }
                    });
                }
            });
        });
    }

    // Google login handling
    if (googleBtn) {
        googleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const clientId = '<?php echo $_ENV["GOOGLE_CLIENT_ID"]; ?>';
            const redirectUri = '<?php echo $_ENV["GOOGLE_REDIRECT_URI"]; ?>';
            const scope = 'email profile';
            
            const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?client_id=${clientId}&redirect_uri=${redirectUri}&response_type=code&scope=${scope}`;
            window.location.href = authUrl;
        });
    }

    // Check for Google user data in session only when returning from Google OAuth
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('code') || urlParams.has('error')) {
        $.ajax({
            url: 'check_google_session.php',
            type: 'GET',
            success: function(response) {
                const data = JSON.parse(response);
                if (data.hasGoogleUser) {
                    const form = document.querySelector('.pre-register__form');
                    if (form) {
                        form.querySelector('#email').value = data.email;
                        form.querySelector('#nome').value = data.name;
                    }
                }
            }
        });
    }
});
