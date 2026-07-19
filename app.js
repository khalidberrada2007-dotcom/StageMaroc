/* StageMaroc — interactions
   Mobile navigation toggle + scroll-reveal animations.
   Written to fail silently and respect prefers-reduced-motion. */
(function () {
  "use strict";

  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---- Mobile nav toggle ------------------------------------------- */
  var toggle = document.querySelector(".nav-toggle");
  var nav = document.getElementById("primary-nav");

  if (toggle && nav) {
    toggle.addEventListener("click", function () {
      var isOpen = nav.classList.toggle("is-open");
      toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
      document.body.style.overflow = isOpen ? "hidden" : "";
    });

    nav.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", function () {
        nav.classList.remove("is-open");
        toggle.setAttribute("aria-expanded", "false");
        document.body.style.overflow = "";
      });
    });
  }

  /* ---- Scroll reveal -------------------------------------------------
     Adds .is-visible to any .reveal element once it enters the viewport. */
  var revealEls = document.querySelectorAll(".reveal");

  if (reduceMotion || !("IntersectionObserver" in window)) {
    revealEls.forEach(function (el) { el.classList.add("is-visible"); });
  } else {
    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15, rootMargin: "0px 0px -40px 0px" }
    );
    revealEls.forEach(function (el) { observer.observe(el); });
  }
})();
function showCustomConfirm(message, onConfirm) {
    const modal = document.getElementById('custom-confirm-modal');
    const msgElement = document.getElementById('modal-message');
    const btnYes = document.getElementById('modal-btn-yes');
    const btnNo = document.getElementById('modal-btn-no');

    if (!modal || !msgElement || !btnYes || !btnNo) {
        if (window.confirm(message)) {
            onConfirm();
        }
        return;
    }
    
    msgElement.innerText = message;
    modal.classList.add('is-active');
    
    btnYes.onclick = function() {
        modal.classList.remove('is-active');
        onConfirm();
    };
    
    btnNo.onclick = function() {
        modal.classList.remove('is-active');
    };
}

// Déconnexion avec une seule confirmation stylisée
if (document.querySelectorAll('.confirm-action').length) {
    document.querySelectorAll('.confirm-action').forEach(function (element) {
        element.addEventListener('click', function (e) {
            e.preventDefault();
            const targetUrl = this.getAttribute('href');
            const msg = this.getAttribute('data-msg') || 'Voulez-vous vraiment vous déconnecter ?';

            showCustomConfirm(msg, function () {
                window.location.href = targetUrl;
            });
        });
    });
}

/* ---- Compteurs animés (section statistiques) ----------------------- */
(function () {
    var counters = document.querySelectorAll('.stat h3[data-count]');
    if (!counters.length) return;

    var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    function animateCount(el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;

        if (reduceMotion || !target) {
            el.textContent = target;
            return;
        }

        var duration = 900;
        var start = null;
        el.textContent = '0';

        function step(timestamp) {
            if (!start) start = timestamp;
            var progress = Math.min((timestamp - start) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
            el.textContent = Math.floor(eased * target);

            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                el.textContent = target;
            }
        }

        requestAnimationFrame(step);
    }

    if (!("IntersectionObserver" in window)) {
        counters.forEach(animateCount);
    } else {
        var counterObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animateCount(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.4 });

        counters.forEach(function (el) { counterObserver.observe(el); });
    }
})();