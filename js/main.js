// Mobile menu toggle
document.querySelector('.mobile-menu').addEventListener('click', function () {
    document.querySelector('.nav-links').classList.toggle('show');
});

// Form validation
document.addEventListener('DOMContentLoaded', function () {
    // Date picker - set min date to today
    const dateInput = document.getElementById('date');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
    }

    // Contact form validation
    const contactForm = document.querySelector('.contact-form form');
    if (contactForm) {
        contactForm.addEventListener('submit', function (e) {
            const name = this.querySelector('input[name="name"]').value.trim();
            const email = this.querySelector('input[name="email"]').value.trim();
            const message = this.querySelector('textarea[name="message"]').value.trim();

            if (!name || !email || !message) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    }

    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    });
});

// Search form validation
const searchForm = document.querySelector('.search-box form') || document.querySelector('.search-form-modern');
if (searchForm) {
    searchForm.addEventListener('submit', function (e) {
        const from = this.querySelector('#from').value;
        const to = this.querySelector('#to').value;
        const date = this.querySelector('#date').value;

        if (!from || !to || !date) {
            e.preventDefault();
            alert('Please fill in all search fields.');
            return;
        }

        if (from === to) {
            e.preventDefault();
            alert('Departure and destination cannot be the same.');
        }
    });
}

// Route search filtering
const routeSearch = document.getElementById('routeSearch');
if (routeSearch) {
    routeSearch.addEventListener('input', function () {
        const term = this.value.toLowerCase();
        const cards = document.querySelectorAll('.route-card');

        cards.forEach(card => {
            const cities = card.querySelector('.route-header').textContent.toLowerCase();
            if (cities.includes(term)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
}