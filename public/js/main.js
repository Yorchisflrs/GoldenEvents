console.log("Golden Hour Events cargado correctamente");

(function () {
    const supportsTransitions = "fetch" in window && "history" in window && "pushState" in history;
    const reducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const duration = reducedMotion ? 0 : 420;

    function qs(selector, root = document) {
        return root.querySelector(selector);
    }

    function qsa(selector, root = document) {
        return Array.from(root.querySelectorAll(selector));
    }

    function initMenu() {
        const menuToggle = qs(".menu-toggle");
        const navbarLinks = qs(".navbar-links");

        if (!menuToggle || !navbarLinks || menuToggle.dataset.bound === "1") {
            return;
        }

        menuToggle.dataset.bound = "1";
        menuToggle.addEventListener("click", function () {
            const isOpen = navbarLinks.classList.toggle("is-open");
            menuToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });
    }

    function initConfirmations(root = document) {
        qsa("[data-confirm]", root).forEach(function (element) {
            if (element.dataset.confirmBound === "1") {
                return;
            }

            element.dataset.confirmBound = "1";
            element.addEventListener("click", function (event) {
                const message = element.getAttribute("data-confirm") || "Confirma esta accion?";
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    }

    function initSmoothScroll(root = document) {
        qsa('a[href^="#"]', root).forEach(function (anchor) {
            if (anchor.dataset.scrollBound === "1") {
                return;
            }

            anchor.dataset.scrollBound = "1";
            anchor.addEventListener("click", function (event) {
                const target = qs(anchor.getAttribute("href"));
                if (target) {
                    event.preventDefault();
                    target.scrollIntoView({ behavior: reducedMotion ? "auto" : "smooth", block: "start" });
                }
            });
        });
    }

    function initReveal(root = document) {
        const items = qsa(".reveal, .stagger-item", root);

        if (reducedMotion || !("IntersectionObserver" in window)) {
            items.forEach((item) => item.classList.add("visible"));
            return;
        }

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add("visible");
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        items.forEach(function (item, index) {
            item.style.transitionDelay = `${index * 40}ms`;
            observer.observe(item);
        });
    }

    function initReservationTotals(root = document) {
        qsa(".reservation-form", root).forEach(function (form) {
            const quantity = qs("[name='quantity']", form);
            const output = qs("[data-reservation-total]", form);
            const unitCents = Number(form.dataset.unitPrice || 0);
            if (!quantity || !output || form.dataset.totalBound === "1") return;
            form.dataset.totalBound = "1";
            const refresh = function () {
                const total = Math.max(0, Number.parseInt(quantity.value, 10) || 0) * unitCents;
                output.textContent = `S/ ${(total / 100).toFixed(2)}`;
            };
            quantity.addEventListener("input", refresh);
            refresh();
        });
    }

    function initCountdowns(root = document) {
        qsa("[data-countdown]", root).forEach(function (element) {
            if (element.dataset.countdownBound === "1") return;
            element.dataset.countdownBound = "1";
            const expires = Date.parse(element.dataset.countdown || "");
            const update = function () {
                const remaining = Math.max(0, expires - Date.now());
                if (!Number.isFinite(expires) || remaining <= 0) {
                    element.textContent = "El plazo de pago venció.";
                    return false;
                }
                const seconds = Math.floor(remaining / 1000);
                const minutes = Math.floor(seconds / 60);
                element.textContent = `Tiempo restante: ${minutes}:${String(seconds % 60).padStart(2, "0")}`;
                return true;
            };
            update();
            const timer = window.setInterval(function () { if (!update()) window.clearInterval(timer); }, 1000);
        });
    }

    function showLoader() {
        const loader = qs("#pageLoader");
        if (loader) {
            loader.classList.add("is-active");
        }
    }

    function hideLoader() {
        const loader = qs("#pageLoader");
        if (loader) {
            loader.classList.remove("is-active");
        }
    }

    function fragmentUrl(url) {
        const target = new URL(url, window.location.origin);
        target.searchParams.set("fragment", "1");
        return target.toString();
    }

    function extractContent(text) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, "text/html");
        const app = doc.querySelector("#app-content");

        if (app) {
            return app.innerHTML;
        }

        const body = doc.body;
        return body && body.innerHTML.trim() ? body.innerHTML : text;
    }

    function shouldIntercept(anchor) {
        if (!supportsTransitions || anchor.dataset.noTransition !== undefined || anchor.target === "_blank") {
            return false;
        }

        const href = anchor.getAttribute("href");
        if (!href || href.startsWith("#")) {
            return false;
        }

        const target = new URL(anchor.href, window.location.origin);
        if (target.origin !== window.location.origin) {
            return false;
        }

        if (!target.pathname.startsWith("/GoldenHoursEvents/")) {
            return false;
        }

        if (target.pathname.includes("/views/auth/logout.php") || target.searchParams.has("add_service") || target.searchParams.has("remove_service")) {
            return false;
        }

        return true;
    }

    async function loadPage(url, push = true) {
        const app = qs("#app-content");
        if (!app) {
            window.location.href = url;
            return;
        }

        try {
            showLoader();
            app.classList.add("page-exit");
            if (duration) {
                await new Promise((resolve) => setTimeout(resolve, duration * 0.55));
            }

            const response = await fetch(fragmentUrl(url), {
                headers: { "X-Requested-With": "fetch" },
                credentials: "same-origin"
            });

            if (!response.ok) {
                throw new Error("No se pudo cargar la pagina.");
            }

            const html = await response.text();
            app.innerHTML = extractContent(html);
            app.classList.remove("page-exit");
            app.classList.add("page-enter");

            if (push) {
                history.pushState({ url }, "", url);
            }

            window.scrollTo({ top: 0, behavior: reducedMotion ? "auto" : "smooth" });
            initPage(app);

            if (duration) {
                setTimeout(() => app.classList.remove("page-enter"), duration);
            } else {
                app.classList.remove("page-enter");
            }
        } catch (error) {
            window.location.href = url;
        } finally {
            hideLoader();
        }
    }

    function initPage(root = document) {
        initMenu();
        initConfirmations(root);
        initSmoothScroll(root);
        initReveal(root);
        initReservationTotals(root);
        initCountdowns(root);
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.body.classList.add("page-ready");
        initPage(document);

        document.addEventListener("click", function (event) {
            const anchor = event.target.closest("a.js-page-link");
            if (!anchor || !shouldIntercept(anchor)) {
                return;
            }

            event.preventDefault();
            loadPage(anchor.href);
        });

        window.addEventListener("popstate", function () {
            loadPage(window.location.href, false);
        });
    });
})();
