(function () {
  'use strict';

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const $ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

  function enableSmoothScroll() {
    if (prefersReducedMotion) return;
    document.addEventListener('click', function (e) {
      const a = e.target.closest('a[href*="#"]');
      if (!a) return;
      const href = a.getAttribute('href');
      if (!href || href.charAt(0) !== '#') return;
      const target = document.getElementById(href.slice(1));
      if (!target) return;
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      target.focus({ preventScroll: true });
    });
  }

  function enableRevealOnScroll() {
    const items = $('.event');
    if (!items.length || prefersReducedMotion) return;
    const io = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    items.forEach(it => {
      it.style.transition = it.style.transition || 'transform .45s cubic-bezier(.2,.9,.2,1), opacity .45s ease';
      it.style.opacity = it.style.opacity || '0';
      it.style.transform = it.style.transform || 'translateY(10px)';
      io.observe(it);
    });
  }

  function applyInViewStyles() {
    document.addEventListener('animationend', () => {});
    const observer = new MutationObserver(muts => {
      muts.forEach(m => {
        if (m.type === 'attributes' && m.attributeName === 'class') {
          const t = m.target;
          if (t.classList.contains('in-view')) {
            t.style.opacity = '1';
            t.style.transform = 'translateY(0)';
          }
        }
      });
    });
    $('.event').forEach(el => observer.observe(el, { attributes: true }));
  }

  function enhanceAlerts() {
    const alerts = $('.alert');
    alerts.forEach(a => {
      if (!a.querySelector('.alert-close')) {
        const btn = document.createElement('button');
        btn.setAttribute('type', 'button');
        btn.className = 'alert-close';
        btn.innerText = '✕';
        btn.style.marginLeft = '12px';
        btn.style.background = 'transparent';
        btn.style.border = 'none';
        btn.style.cursor = 'pointer';
        btn.setAttribute('aria-label', 'Dismiss message');
        btn.addEventListener('click', () => a.remove());
        a.appendChild(btn);
      }
      if (a.classList.contains('success')) {
        setTimeout(() => {
          try { a.style.transition = 'opacity .35s ease'; a.style.opacity = '0'; setTimeout(()=>a.remove(), 360); } catch(e){}
        }, 4200);
      }
    });
  }

  function enableClientValidation() {
    const forms = $('.validate');
    if (!forms.length) return;

    function showFieldError(field, message) {
      const old = field.parentElement.querySelector('.field-error');
      if (old) old.remove();
      const el = document.createElement('div');
      el.className = 'field-error';
      el.style.color = '#9b1e1e';
      el.style.fontSize = '0.9rem';
      el.style.marginTop = '6px';
      el.textContent = message;
      field.parentElement.appendChild(el);
      field.classList.add('input-error');
      field.focus();
    }

    function clearFieldError(field) {
      const old = field.parentElement.querySelector('.field-error');
      if (old) old.remove();
      field.classList.remove('input-error');
    }

    forms.forEach(form => {
      form.addEventListener('submit', function (e) {
        const fields = Array.from(form.elements).filter(f => f.tagName && ['INPUT', 'TEXTAREA', 'SELECT'].includes(f.tagName));
        let stop = false;
        fields.forEach(f => clearFieldError(f));
        fields.forEach(f => {
          if (stop) return;
          if (f.hasAttribute('required') && !f.value.trim()) {
            showFieldError(f, 'This field is required');
            stop = true;
          }
        });
        if (stop) { e.preventDefault(); return; }
        const email = form.querySelector('input[type="email"]');
        if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
          showFieldError(email, 'Enter a valid email');
          e.preventDefault(); return;
        }
        const pass = form.querySelector('input[type="password"]');
        if (pass && pass.value && pass.value.length > 0 && pass.value.length < 6) {
          showFieldError(pass, 'Password must be at least 6 characters');
          e.preventDefault(); return;
        }
        const confirm = form.querySelector('input[name="confirm"], input[name="password_confirm"], input[name="password-confirm"]');
        if (confirm && pass && pass.value !== confirm.value) {
          showFieldError(confirm, 'Passwords do not match');
          e.preventDefault(); return;
        }
      });
    });
  }

  function enableConfirmModal() {
    let modal = null;
    function createModal() {
      modal = document.createElement('div');
      modal.className = 'confirm-modal';
      modal.style.position = 'fixed';
      modal.style.inset = '0';
      modal.style.display = 'grid';
      modal.style.placeItems = 'center';
      modal.style.background = 'rgba(2,6,23,0.45)';
      modal.style.zIndex = '1200';
      modal.innerHTML = `
        <div role="dialog" aria-modal="true" aria-labelledby="confirm-title" style="background:#fff;padding:18px;border-radius:10px;max-width:420px;width:92%;box-shadow:0 10px 30px rgba(2,6,23,0.2);">
          <h3 id="confirm-title" style="margin:0 0 8px 0;">Please confirm</h3>
          <p id="confirm-message" style="margin:0 0 12px 0;color:#222;"></p>
          <div style="display:flex;gap:8px;justify-content:flex-end;">
            <button class="btn secondary" id="confirm-cancel">Cancel</button>
            <button class="btn" id="confirm-ok">Yes, continue</button>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
      modal.querySelector('#confirm-cancel').addEventListener('click', closeModal);
      modal.querySelector('#confirm-ok').addEventListener('click', acceptModal);
      modal.addEventListener('keydown', trapKeyDown);
    }

    let activeResolve = null;
    let activeReject = null;
    let activeTarget = null;

    function trapKeyDown(e) {
      if (e.key === 'Escape') { closeModal(); }
      if (e.key === 'Tab') {
        const focusables = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (!focusables.length) return;
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault(); last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault(); first.focus();
        }
      }
    }

    function openModal(message) {
      if (!modal) createModal();
      modal.querySelector('#confirm-message').textContent = message || 'Are you sure?';
      modal.style.display = 'grid';
      activeTarget = document.activeElement;
      setTimeout(()=> modal.querySelector('#confirm-ok').focus(), 20);
    }

    function closeModal() {
      if (!modal) return;
      modal.style.display = 'none';
      if (activeTarget) activeTarget.focus();
      activeTarget = null;
    }

    function acceptModal() {
      if (activeTarget) {
        activeTarget.__confirmAccepted = true;
        if (activeTarget.tagName === 'FORM') {
          activeTarget.submit();
        } else {
          activeTarget.click();
        }
        setTimeout(()=> activeTarget.__confirmAccepted = false, 100);
      }
      closeModal();
    }

    document.addEventListener('click', function (e) {
      const el = e.target.closest('[data-confirm]');
      if (!el) return;
      const message = el.getAttribute('data-confirm') || 'Are you sure?';
      if (el.tagName === 'BUTTON' && el.type === 'submit' && el.form) {
        e.preventDefault();
        if (el.__confirmAccepted) return;
        openModal(message);
        activeTarget = el.form;
        return;
      }
      if (el.tagName === 'FORM') {
        e.preventDefault();
        if (el.__confirmAccepted) return;
        openModal(message);
        activeTarget = el;
        return;
      }
      if (el.tagName === 'A') {
        e.preventDefault();
        openModal(message);
        activeTarget = el;
        return;
      }
      e.preventDefault();
      openModal(message);
      activeTarget = el;
    });
  }

  function enableMobileMenu() {
    const breakpoint = 800;
    function shouldEnable() { return window.innerWidth <= breakpoint; }
    const header = document.querySelector('header.container');
    if (!header) return;
    const nav = header.querySelector('.navbar');
    if (!nav) return;

    let toggle = document.getElementById('mobile-nav-toggle');
    if (!toggle) {
      toggle = document.createElement('button');
      toggle.id = 'mobile-nav-toggle';
      toggle.className = 'btn ghost';
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Toggle menu');
      toggle.innerHTML = '☰';
      const brand = header.querySelector('.brand');
      if (brand && brand.parentNode) {
        brand.parentNode.insertBefore(toggle, brand.nextSibling);
      } else {
        header.insertBefore(toggle, header.firstChild);
      }
    }

    function update() {
      if (!shouldEnable()) {
        toggle.style.display = 'none';
        nav.style.display = '';
        nav.removeAttribute('data-mobile-open');
        return;
      }
      toggle.style.display = '';
      if (!toggle.classList.contains('open')) {
        nav.style.display = 'none';
        nav.setAttribute('aria-hidden', 'true');
      }
    }

    toggle.addEventListener('click', function () {
      const open = !toggle.classList.contains('open');
      toggle.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', String(open));
      if (open) {
        nav.style.display = 'flex';
        nav.style.flexDirection = 'column';
        nav.setAttribute('data-mobile-open', '1');
        nav.removeAttribute('aria-hidden');
      } else {
        nav.style.display = 'none';
        nav.removeAttribute('data-mobile-open');
        nav.setAttribute('aria-hidden', 'true');
      }
    });

    window.addEventListener('resize', update);
    update();
  }

  function enableCountUp() {
    if (prefersReducedMotion) return;
    const counters = $('.count');
    if (!counters.length) return;
    const io = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        const target = parseInt(el.textContent.replace(/[^\d]/g, ''), 10) || 0;
        let start = 0;
        const duration = 900;
        const step = (timestampStart) => {
          const now = performance.now();
          const elapsed = Math.min(duration, now - timestampStart);
          const progress = elapsed / duration;
          const current = Math.floor(progress * target);
          el.textContent = current;
          if (elapsed < duration) requestAnimationFrame(() => step(timestampStart));
          else el.textContent = target;
        };
        requestAnimationFrame(ts => step(ts));
        io.unobserve(el);
      });
    }, { threshold: 0.2 });
    counters.forEach(c => io.observe(c));
  }

  function enhanceSkipLink() {
    const skip = document.querySelector('a[href="#main-content"]');
    if (!skip) return;
    skip.addEventListener('click', e => {
      const main = document.getElementById('main-content');
      if (main) {
        e.preventDefault();
        main.tabIndex = -1;
        main.focus();
        main.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth' });
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    enableSmoothScroll();
    enableRevealOnScroll();
    applyInViewStyles();
    enhanceAlerts();
    enableClientValidation();
    enableConfirmModal();
    enableMobileMenu();
    enableCountUp();
    enhanceSkipLink();
    console.debug('App enhancements loaded');
  });

})();