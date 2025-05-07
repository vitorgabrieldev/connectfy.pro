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
    const googleBtn = document.querySelector('.pre-register__google');
    
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
    
    // Function to update Google button state
    function updateGoogleButtonState() {
        const hasSelectedInterest = document.querySelector('.pre-register__option--active') !== null;
        googleBtn.disabled = !hasSelectedInterest;
        googleBtn.style.opacity = hasSelectedInterest ? '1' : '0.5';
        googleBtn.style.cursor = hasSelectedInterest ? 'pointer' : 'not-allowed';
    }
    
    interestOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Update all options
            interestOptions.forEach(opt => {
                const isActive = opt === this;
                opt.classList.toggle('pre-register__option--active', isActive);
                updateIcon(opt, isActive);
            });
            
            // Update Google button state
            updateGoogleButtonState();
        });
    });

    // Initial Google button state
    updateGoogleButtonState();

    // Form handling
    const form = document.querySelector('.pre-register__form');

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
                error: function() {
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
            
            const activeOption = document.querySelector('.pre-register__option--active');
            if (!activeOption) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção!',
                    text: 'Por favor, selecione se você é prestador ou contratante antes de continuar.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#405FF2',
                    background: '#fff'
                });
                return;
            }

            // Get Google configuration
            $.ajax({
                url: 'config/google_config.php',
                type: 'GET',
                success: function(config) {
                    // Save selected interest in session
                    const interest = activeOption.textContent.includes('anunciar') ? 'provider' : 'client';
                    $.ajax({
                        url: 'save_interest.php',
                        type: 'POST',
                        data: { interest: interest },
                        success: function() {
                            const scope = 'email profile';
                            const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?client_id=${config.clientId}&redirect_uri=${config.redirectUri}&response_type=code&scope=${scope}`;
                            window.location.href = authUrl;
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: 'Não foi possível salvar sua preferência. Tente novamente.',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#405FF2'
                            });
                        }
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Não foi possível iniciar o login com Google. Tente novamente.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#405FF2'
                    });
                }
            });
        });
    }

    // Check for Google user data in session only when returning from Google OAuth
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('code') || urlParams.has('error') || urlParams.has('google_success')) {
        // Show loading state
        Swal.fire({
            title: 'Processando...',
            text: 'Estamos realizando seu pré-cadastro',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Check if there was an error
        if (urlParams.has('error')) {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: 'Não foi possível fazer login com o Google. Tente novamente.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#405FF2',
                background: '#fff'
            });
            return;
        }

        // Remove URL parameters after reading them
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);

        // Check session for Google user data
        $.ajax({
            url: 'check_google_session.php',
            type: 'GET',
            success: function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    console.log('Session check response:', data); // Debug log
                    
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Pré-cadastro realizado!',
                            text: data.message || 'Seu cadastro foi realizado com sucesso. Acompanhe seu e-mail para mais informações.',
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
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: data.message || 'Não foi possível recuperar seus dados do Google. Por favor, tente novamente.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#405FF2',
                            background: '#fff'
                        });
                    }
                } catch (error) {
                    console.error('Erro ao processar resposta:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Ocorreu um erro ao processar os dados do Google.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#405FF2',
                        background: '#fff'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', {xhr, status, error}); // Debug log
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Não foi possível conectar ao servidor. Por favor, tente novamente.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#405FF2',
                    background: '#fff'
                });
            }
        });
    }
});
