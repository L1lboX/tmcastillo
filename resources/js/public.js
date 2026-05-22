document.addEventListener("DOMContentLoaded", () => {
    initLoader();
    initNavbar();
    initScrollReveal();
    initMobileMenu();
    initCounters();
    initContactForm();
    initGallery();
});

function initLoader() {
    const loader = document.getElementById("page-loader");
    if (!loader) return;

    window.addEventListener("load", () => {
        setTimeout(() => {
            loader.classList.add("hidden");
            document.body.style.overflow = "";
        }, 600);
    });

    setTimeout(() => {
        loader.classList.add("hidden");
        document.body.style.overflow = "";
    }, 2000);
}

function initNavbar() {
    const navbar = document.getElementById("navbar");
    if (!navbar) return;

    let lastScroll = 0;

    window.addEventListener("scroll", () => {
        const current = window.scrollY;

        if (current > 60) {
            navbar.classList.add("scrolled");
        } else {
            navbar.classList.remove("scrolled");
        }

        if (current > 300 && current > lastScroll) {
            navbar.style.transform = "translateY(-100%)";
        } else {
            navbar.style.transform = "translateY(0)";
        }

        lastScroll = current;
    });

    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener("click", (e) => {
            e.preventDefault();
            const target = document.querySelector(anchor.getAttribute("href"));
            if (target) {
                target.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        });
    });
}

function initScrollReveal() {
    const revealElements = document.querySelectorAll(
        ".section-hidden, .fade-in, .fade-in-left, .fade-in-right, .scale-in"
    );
    const staggerElements = document.querySelectorAll(".stagger-children");

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("visible");
                    if (entry.target.classList.contains("stagger-children")) {
                        entry.target.classList.add("stagger-visible");
                    }
                    observer.unobserve(entry.target);
                }
            });
        },
        {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px",
        }
    );

    revealElements.forEach((el) => observer.observe(el));
    staggerElements.forEach((el) => observer.observe(el));
}

function initMobileMenu() {
    const toggle = document.getElementById("menu-toggle");
    const menu = document.getElementById("mobile-menu");
    const close = document.getElementById("menu-close");
    const links = menu?.querySelectorAll("a");

    if (!toggle || !menu) return;

    function open() {
        menu.classList.add("open");
        document.body.style.overflow = "hidden";
    }

    function closeMenu() {
        menu.classList.remove("open");
        document.body.style.overflow = "";
    }

    toggle.addEventListener("click", open);
    if (close) close.addEventListener("click", closeMenu);

    links?.forEach((link) => {
        link.addEventListener("click", closeMenu);
    });
}

function initCounters() {
    const counters = document.querySelectorAll(".counter");
    if (!counters.length) return;

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const target = parseInt(entry.target.dataset.target);
                    const duration = parseInt(entry.target.dataset.duration) || 2000;
                    animateCounter(entry.target, target, duration);
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.5 }
    );

    counters.forEach((counter) => observer.observe(counter));
}

function animateCounter(element, target, duration) {
    const start = 0;
    const startTime = performance.now();

    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        const eased = 1 - Math.pow(1 - progress, 3);
        const current = Math.floor(start + (target - start) * eased);

        element.textContent = current.toLocaleString();

        if (progress < 1) {
            requestAnimationFrame(update);
        } else {
            element.textContent = target.toLocaleString();
        }
    }

    requestAnimationFrame(update);
}

function initContactForm() {
    const form = document.getElementById("contact-form");
    if (!form) return;

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const inputs = form.querySelectorAll(".contact-input");
        let valid = true;

        inputs.forEach((input) => {
            input.classList.remove("error");
            if (input.required && !input.value.trim()) {
                input.classList.add("error");
                valid = false;
            }
        });

        if (!valid) return;

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = `
            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Enviando...
        `;
        submitBtn.disabled = true;

        const formData = new FormData(form);
        formData.append("_token", document.querySelector('meta[name="csrf-token"]')?.content || "");

        try {
            const response = await fetch("/contacto", {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            if (!response.ok) throw new Error("Error al enviar");

            const successMsg = document.getElementById("form-success");
            if (successMsg) {
                successMsg.classList.add("show");
                form.reset();
            }

            setTimeout(() => {
                if (successMsg) successMsg.classList.remove("show");
            }, 5000);
        } catch {
            alert("Hubo un error al enviar el mensaje. Intenta nuevamente.");
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
}

function initGallery() {
    const gallery = document.getElementById("gallery-grid");
    if (!gallery) return;

    // Apply lazy loading to images
    const images = gallery.querySelectorAll("img");
    images.forEach((img) => {
        img.loading = "lazy";
    });

    const filterButtons = document.querySelectorAll(".filter-btn");
    const galleryItems = gallery.querySelectorAll(".gallery-item");

    if (!filterButtons.length || !galleryItems.length) return;

    filterButtons.forEach((btn) => {
        btn.addEventListener("click", () => {
            const filterValue = btn.getAttribute("data-filter");

            // Update active state of buttons
            filterButtons.forEach((b) => {
                if (b === btn) {
                    b.classList.add("active", "bg-white/5", "text-white");
                    b.classList.remove("bg-transparent", "text-white/60");
                } else {
                    b.classList.remove("active", "bg-white/5", "text-white");
                    b.classList.add("bg-transparent", "text-white/60");
                }
            });

            // Filter gallery items
            galleryItems.forEach((item) => {
                const category = item.getAttribute("data-category");

                if (filterValue === "all" || category === filterValue) {
                    // Show item
                    item.style.display = "";
                    // Small delay to trigger smooth transition
                    setTimeout(() => {
                        item.style.opacity = "1";
                        item.style.transform = "scale(1)";
                        item.style.pointerEvents = "auto";
                    }, 50);
                } else {
                    // Animate out
                    item.style.opacity = "0";
                    item.style.transform = "scale(0.92)";
                    item.style.pointerEvents = "none";

                    // Hide completely after transition finishes
                    setTimeout(() => {
                        if (item.style.opacity === "0") {
                            item.style.display = "none";
                        }
                    }, 400);
                }
            });
        });
    });
}
