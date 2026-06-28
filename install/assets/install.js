/**
 * eFiction Installer Front-End Wizard
 *
 * Progressive enhancement: the installer form works without JavaScript,
 * but this script provides a step-by-step wizard with AJAX database testing
 * and installation.
 */
(function () {
    'use strict';

    document.documentElement.classList.remove('no-js');

    const form = document.getElementById('install-form');
    const wizard = document.getElementById('wizard');
    const alerts = document.getElementById('global-alerts');
    const steps = wizard.querySelectorAll('.step-indicator');
    const panels = wizard.querySelectorAll('.step-panel');
    const btnStep1Next = document.getElementById('btn-step-1-next');
    const btnStep2Next = document.getElementById('btn-step-2-next');
    const btnTestDb = document.getElementById('btn-test-db');
    const btnInstall = document.getElementById('btn-install');
    const mailMethod = document.getElementById('mail_method');
    const smtpPanel = document.getElementById('smtp-settings');
    const dbCreate = document.getElementById('db_create');

    let currentStep = 1;
    let dbTested = false;
    let installing = false;

    function showPanel(step) {
        panels.forEach(panel => {
            const panelStep = panel.getAttribute('data-step');
            if (panelStep === String(step)) {
                panel.classList.add('active');
            } else {
                panel.classList.remove('active');
            }
        });

        steps.forEach(indicator => {
            const indicatorStep = parseInt(indicator.getAttribute('data-step'), 10);
            indicator.classList.remove('active', 'complete');
            if (indicatorStep === step) {
                indicator.classList.add('active');
            } else if (indicatorStep < step) {
                indicator.classList.add('complete');
            }
        });

        currentStep = step;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function showAlert(message, type = 'error') {
        alerts.innerHTML = '';
        const alert = document.createElement('div');
        alert.className = 'alert alert-' + type;
        alert.innerHTML = '<p>' + escapeHtml(message) + '</p>';
        alerts.appendChild(alert);
    }

    function clearAlert() {
        alerts.innerHTML = '';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function validatePanel(step) {
        const panel = wizard.querySelector('.step-panel[data-step="' + step + '"]');
        if (!panel) return false;

        const inputs = panel.querySelectorAll('input[required], select[required]');
        let valid = true;

        inputs.forEach(input => {
            if (!input.checkValidity()) {
                valid = false;
                input.reportValidity();
            }
        });

        if (step === 4) {
            const password = document.getElementById('admin_password');
            const confirm = document.getElementById('admin_password_confirm');
            if (password.value !== confirm.value) {
                valid = false;
                confirm.setCustomValidity('Passwords do not match.');
                confirm.reportValidity();
            } else {
                confirm.setCustomValidity('');
            }
        }

        return valid;
    }

    function gatherFormData() {
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            if (key.endsWith('[]')) {
                if (!data[key]) data[key] = [];
                data[key].push(value);
            } else {
                data[key] = value;
            }
        });
        return data;
    }

    function setButtonLoading(button, loading) {
        if (loading) {
            button.disabled = true;
            button.dataset.originalText = button.textContent.trim();
            button.innerHTML = '<span class="spinner"></span> ' + escapeHtml(button.dataset.originalText);
        } else {
            button.disabled = false;
            button.textContent = button.dataset.originalText || button.textContent.replace(/^\s*\S+\s+/, '');
        }
    }

    function ajax(action, payload) {
        const formData = new FormData();
        formData.append('action', action);
        for (const key in payload) {
            if (Object.prototype.hasOwnProperty.call(payload, key)) {
                formData.append(key, payload[key]);
            }
        }

        return fetch('/install/', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        }).then(response => {
            if (!response.ok) {
                throw new Error('Server returned ' + response.status);
            }
            return response.json();
        });
    }

    // Step 1: requirements already rendered server-side; just continue.
    if (btnStep1Next) {
        btnStep1Next.addEventListener('click', function () {
            if (!btnStep1Next.disabled) {
                showPanel(2);
            }
        });
    }

    // Database test
    if (btnTestDb) {
        btnTestDb.addEventListener('click', function () {
            clearAlert();
            const data = gatherFormData();
            setButtonLoading(btnTestDb, true);
            dbTested = false;
            btnStep2Next.disabled = true;

            ajax('test_db', {
                db_host: data.db_host,
                db_database: data.db_database,
                db_user: data.db_user,
                db_password: data.db_password,
                db_create: data.db_create || '0',
            }).then(result => {
                setButtonLoading(btnTestDb, false);
                if (result.ok) {
                    showAlert(result.message, 'success');
                    dbTested = true;
                    btnStep2Next.disabled = false;
                } else {
                    showAlert(result.message, 'error');
                }
            }).catch(err => {
                setButtonLoading(btnTestDb, false);
                showAlert('Could not test database connection: ' + err.message, 'error');
            });
        });
    }

    // Step 2: database
    if (btnStep2Next) {
        btnStep2Next.addEventListener('click', function () {
            if (!validatePanel(2)) return;
            if (!dbTested) {
                showAlert('Please test the database connection before continuing.', 'error');
                return;
            }
            showPanel(3);
        });
    }

    // Generic next/prev buttons
    wizard.querySelectorAll('[data-next]').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!validatePanel(currentStep)) return;
            showPanel(currentStep + 1);
        });
    });

    wizard.querySelectorAll('[data-prev]').forEach(btn => {
        btn.addEventListener('click', function () {
            showPanel(currentStep - 1);
        });
    });

    // Mail method conditional panel
    if (mailMethod && smtpPanel) {
        mailMethod.addEventListener('change', function () {
            smtpPanel.classList.toggle('open', mailMethod.value === 'smtp');
        });
    }

    // Review panel population
    function populateReview() {
        const data = gatherFormData();
        const dbEl = document.getElementById('review-database');
        const siteEl = document.getElementById('review-site');
        const adminEl = document.getElementById('review-admin');

        const mask = '••••••••';
        dbEl.innerHTML = `
            <dt>Host</dt><dd>${escapeHtml(data.db_host || '')}</dd>
            <dt>Database</dt><dd>${escapeHtml(data.db_database || '')}</dd>
            <dt>User</dt><dd>${escapeHtml(data.db_user || '')}</dd>
            <dt>Prefix</dt><dd>${escapeHtml(data.db_prefix || '')}</dd>
            <dt>Create if missing</dt><dd>${data.db_create ? 'Yes' : 'No'}</dd>
        `;

        siteEl.innerHTML = `
            <dt>Title</dt><dd>${escapeHtml(data.site_title || '')}</dd>
            <dt>Email</dt><dd>${escapeHtml(data.site_email || '')}</dd>
            <dt>URL</dt><dd>${escapeHtml(data.site_url || '')}</dd>
            <dt>Language</dt><dd>${escapeHtml(data.site_language || '')}</dd>
            <dt>Timezone</dt><dd>${escapeHtml(data.site_timezone || '')}</dd>
            <dt>Mail method</dt><dd>${escapeHtml(data.mail_method || '')}</dd>
        `;

        adminEl.innerHTML = `
            <dt>Penname</dt><dd>${escapeHtml(data.admin_penname || '')}</dd>
            <dt>Real name</dt><dd>${escapeHtml(data.admin_realname || '')}</dd>
            <dt>Email</dt><dd>${escapeHtml(data.admin_email || '')}</dd>
            <dt>Password</dt><dd>${mask}</dd>
        `;
    }

    // Show review panel when moving to step 5
    const reviewTriggers = [document.getElementById('btn-step-2-next')];
    // Add listener to any next button that would advance to step 5
    wizard.querySelectorAll('[data-next]').forEach(btn => {
        btn.addEventListener('click', function () {
            if (currentStep === 4 && validatePanel(4)) {
                populateReview();
            }
        });
    });

    // Installation submission
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (installing) return;

            clearAlert();
            installing = true;
            setButtonLoading(btnInstall, true);

            const data = gatherFormData();
            ajax('install', data).then(result => {
                setButtonLoading(btnInstall, false);
                if (result.ok) {
                    showPanel('success');
                    // Hide the form navigation
                    form.style.display = 'none';
                    steps.forEach(s => s.classList.add('complete'));
                } else {
                    showAlert(result.message || 'Installation failed.', 'error');
                    installing = false;
                }
            }).catch(err => {
                setButtonLoading(btnInstall, false);
                showAlert('Installation request failed: ' + err.message, 'error');
                installing = false;
            });
        });
    }

    // Password match validation in real time
    const password = document.getElementById('admin_password');
    const confirm = document.getElementById('admin_password_confirm');
    if (password && confirm) {
        function checkMatch() {
            if (confirm.value && password.value !== confirm.value) {
                confirm.setCustomValidity('Passwords do not match.');
            } else {
                confirm.setCustomValidity('');
            }
        }
        password.addEventListener('input', checkMatch);
        confirm.addEventListener('input', checkMatch);
    }
})();
