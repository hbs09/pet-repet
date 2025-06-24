 // Add scroll effect to navbar
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('.top-nav');
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
            
            // Close dropdown on scroll
            const dropdownItems = document.querySelectorAll('.dropdown-item');
            dropdownItems.forEach(item => {
                item.classList.remove('dropdown-open');
            });
        });

        // Dropdown management
        const dropdownItems = document.querySelectorAll('.dropdown-item');
        
        dropdownItems.forEach(item => {
            const dropdown = item.querySelector('.mega-dropdown');
            
            item.addEventListener('mouseenter', function() {
                this.classList.add('dropdown-open');
            });
            
            item.addEventListener('mouseleave', function() {
                this.classList.remove('dropdown-open');
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown-item')) {
                dropdownItems.forEach(item => {
                    item.classList.remove('dropdown-open');
                });
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add to cart functionality
        document.querySelectorAll('.product-button').forEach(button => {
            button.addEventListener('click', function() {
                const cartCount = document.querySelector('.cart-count');
                let count = parseInt(cartCount.textContent);
                cartCount.textContent = count + 1;
                
                // Add visual feedback
                this.textContent = 'Adicionado!';
                this.style.background = '#1abc9c';
                setTimeout(() => {
                    this.textContent = 'Adicionar ao Carrinho';
                    this.style.background = '#3498db';
                }, 2000);
            });
        });