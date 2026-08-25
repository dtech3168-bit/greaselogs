/**
 * DeluxeSocial - Main JavaScript
 */


const hamburger = document.querySelector('.hamburger');
const navLinks = document.querySelector('.nav-links');

hamburger.addEventListener('click', () => {
    navLinks.classList.toggle('active');
});


document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Navbar scroll effect
    const navbar = document.querySelector('.navbar');

window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});
    // Counter animation
    const counters = document.querySelectorAll('.counter');
    const speed = 900;

    const animateCounters = () => {
        counters.forEach(counter => {
            const updateCount = () => {
                const target = +counter.innerText.replace('+', '').replace('k', '000');
                const count = +counter.getAttribute('data-count') || 0;
                const inc = target / speed;

                if (count < target) {
                    const newCount = Math.ceil(count + inc);
                    counter.setAttribute('data-count', newCount);
                    counter.innerText = (newCount >= 1000 ? (newCount / 1000).toFixed(0) + 'k' : newCount) + '+';
                    setTimeout(updateCount, 1);
                } else {
                    counter.innerText = (target >= 1000 ? (target / 1000).toFixed(0) + 'k' : target) + '+';
                }
            };
            updateCount();
        });
    };

    // Intersection Observer for counters
    const observerOptions = {
        threshold: 0.5
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    const statsSection = document.querySelector('.stats');
    if (statsSection) {
        observer.observe(statsSection);
    }

    // Toast Notifications (Simple implementation)
    window.showToast = function(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerText = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    };
});
