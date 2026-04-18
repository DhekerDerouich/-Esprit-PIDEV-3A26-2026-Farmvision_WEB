// ============================================
// FARMVISION - INTERACTIONS MODERNES 2026
// ============================================

// Compteurs animés
const counters = document.querySelectorAll('.counter');

const animateCounter = (counter) => {
    const target = parseInt(counter.dataset.target);
    const duration = 2000;
    const step = target / (duration / 16);
    let current = 0;
    
    const updateCounter = () => {
        current += step;
        if (current < target) {
            counter.textContent = Math.floor(current);
            requestAnimationFrame(updateCounter);
        } else {
            counter.textContent = target;
        }
    };
    
    updateCounter();
};

// Observer pour les compteurs
const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateCounter(entry.target);
            counterObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

counters.forEach(counter => counterObserver.observe(counter));

// Animation des cercles de progression
const progressRings = document.querySelectorAll('.progress-ring-fill');
progressRings.forEach(ring => {
    const value = ring.dataset.value || 75;
    const circumference = 220;
    const offset = circumference - (circumference * value) / 100;
    ring.style.strokeDasharray = circumference;
    ring.style.strokeDashoffset = offset;
});

// Scroll reveal
const revealElements = document.querySelectorAll('.fade-in-up, .fade-in-left, .fade-in-right, .value-card, .team-card, .equipment-card, .stat-card-glass');

const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('active');
            revealObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.1 });

revealElements.forEach(el => revealObserver.observe(el));

// Cartes valeurs - effet hover
const valueCards = document.querySelectorAll('.value-card');
valueCards.forEach(card => {
    card.addEventListener('mouseenter', () => {
        const progress = card.querySelector('.value-progress');
        if (progress) progress.style.width = '100%';
    });
    
    card.addEventListener('mouseleave', () => {
        const progress = card.querySelector('.value-progress');
        if (progress) progress.style.width = '0%';
    });
});

// Slider équipements
let currentSlide = 0;
const track = document.querySelector('.equipments-track');
const prevBtn = document.querySelector('.prev-btn');
const nextBtn = document.querySelector('.next-btn');

if (track && prevBtn && nextBtn) {
    const cards = track.children;
    const cardsPerView = 3;
    const maxSlide = Math.ceil(cards.length / cardsPerView) - 1;
    
    const updateSlider = () => {
        const offset = -currentSlide * 100;
        track.style.transform = `translateX(${offset}%)`;
    };
    
    prevBtn.addEventListener('click', () => {
        if (currentSlide > 0) {
            currentSlide--;
            updateSlider();
        }
    });
    
    nextBtn.addEventListener('click', () => {
        if (currentSlide < maxSlide) {
            currentSlide++;
            updateSlider();
        }
    });
}

// Rappels pour maintenances
const reminderBtns = document.querySelectorAll('.reminder-btn');
reminderBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
        const equipment = btn.dataset.equipment;
        const date = btn.dataset.date;
        
        // Notification style moderne
        const notification = document.createElement('div');
        notification.className = 'flash-message flash-success';
        notification.innerHTML = `🔔 Rappel ajouté pour ${equipment} le ${date}`;
        notification.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 12px 20px;
            background: var(--green-mid);
            color: white;
            border-radius: 8px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'fadeOut 0.5s ease forwards';
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    });
});

// Animation des particules hero
const heroParticles = document.querySelector('.hero-particles');
if (heroParticles) {
    setInterval(() => {
        heroParticles.style.transform = 'rotate(1deg)';
        setTimeout(() => {
            heroParticles.style.transform = 'rotate(-1deg)';
        }, 100);
        setTimeout(() => {
            heroParticles.style.transform = 'rotate(0deg)';
        }, 200);
    }, 5000);
}

// Effet de glassmorphism sur les cartes au scroll
const glassCards = document.querySelectorAll('.glass-stats-card, .stat-card-glass, .mission-card-glass');
window.addEventListener('scroll', () => {
    glassCards.forEach(card => {
        const rect = card.getBoundingClientRect();
        const scrolled = window.scrollY;
        if (rect.top < window.innerHeight && rect.bottom > 0) {
            card.style.transform = `translateY(${scrolled * 0.02}px)`;
        }
    });
});

// Animation des nombres dans les stats
const animateStatNumbers = () => {
    const statNumbers = document.querySelectorAll('.stat-number');
    statNumbers.forEach(stat => {
        if (!stat.classList.contains('counter')) {
            const text = stat.textContent;
            if (text.includes('+') || text.includes('%')) {
                const number = parseInt(text);
                if (!isNaN(number)) {
                    let current = 0;
                    const target = number;
                    const duration = 2000;
                    const step = target / (duration / 16);
                    
                    const update = () => {
                        current += step;
                        if (current < target) {
                            stat.textContent = Math.floor(current) + (text.includes('%') ? '%' : '+');
                            requestAnimationFrame(update);
                        } else {
                            stat.textContent = text;
                        }
                    };
                    
                    const observer = new IntersectionObserver((entries) => {
                        if (entries[0].isIntersecting) {
                            update();
                            observer.unobserve(stat);
                        }
                    });
                    
                    observer.observe(stat);
                }
            }
        }
    });
};

animateStatNumbers();

// Parallax doux sur la hero section
window.addEventListener('scroll', () => {
    const hero = document.querySelector('.hero-section');
    if (hero) {
        const scrolled = window.scrollY;
        hero.style.transform = `translateY(${scrolled * 0.3}px)`;
    }
});