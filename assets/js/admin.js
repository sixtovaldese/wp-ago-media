(function () {
    'use strict';

    var $ = document.querySelector.bind(document);
    var $$ = document.querySelectorAll.bind(document);

    var restUrl = agomediaMedia.restUrl;
    var t = agomediaMedia.i18n || {};

    /*
     * A record whose file is gone gets a placeholder instead of a broken image,
     * with the file-missing state spelled out for whoever reads the table.
     */
    function thumbCell(item) {
        if (item.file_missing || !item.thumbnail_url) {
            return '<span class="ago-thumb ago-thumb-missing" title="' + escHtml(t.fileMissing || '') + '">' +
                '<span class="dashicons dashicons-format-image"></span></span>';
        }
        return '<img class="ago-thumb" src="' + escHtml(item.thumbnail_url) + '" alt="" loading="lazy">';
    }

    function fill(template, value) {
        return String(template || '').replace('%s', value);
    }
    var nonce   = agomediaMedia.nonce;
    var settings = agomediaMedia.settings;

    var saveBtn   = $('#ago-save-settings');
    var statusBox = $('#ago-media-status');

    if (!saveBtn) return;

    function initSettings() {
        $$('[data-key]').forEach(function (el) {
            var key = el.getAttribute('data-key');
            if (el.type === 'checkbox') {
                el.checked = !!settings[key];
            } else if (el.type === 'range' || el.type === 'number') {
                el.value = settings[key] || el.value;
            }
        });

        var qualitySlider = $('#ago-webp-quality');
        var qualityValue  = $('#ago-quality-value');
        if (qualitySlider && qualityValue) {
            qualityValue.textContent = qualitySlider.value;
            qualitySlider.addEventListener('input', function () {
                qualityValue.textContent = this.value;
            });
        }
    }

    initSettings();

    saveBtn.addEventListener('click', function () {
        var data = {};

        $$('[data-key]').forEach(function (el) {
            var key = el.getAttribute('data-key');
            if (el.type === 'checkbox') {
                data[key] = el.checked;
            } else if (el.type === 'range' || el.type === 'number') {
                data[key] = parseInt(el.value, 10);
            }
        });

        saveBtn.disabled = true;
        saveBtn.textContent = t.saving;

        fetch(restUrl + '/settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce,
            },
            body: JSON.stringify(data),
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.saved) {
                settings = res.settings;
                showStatus('success', t.saved);
            } else {
                showStatus('error', t.saveFailed);
            }
        })
        .catch(function (err) {
            showStatus('error', t.errorLabel + ': ' + err.message);
        })
        .finally(function () {
            saveBtn.disabled = false;
            saveBtn.textContent = t.saveBtn;
        });
    });

    function showStatus(type, message) {
        statusBox.style.display = 'block';
        statusBox.className = type;
        statusBox.textContent = message;
        setTimeout(function () {
            statusBox.style.display = 'none';
        }, 3000);
    }

    function loadStats() {
        fetch(restUrl + '/stats', {
            headers: { 'X-WP-Nonce': nonce },
        })
        .then(function (r) { return r.json(); })
        .then(function (stats) {
            var converted = $('#ago-stat-converted');
            var saved     = $('#ago-stat-saved');
            if (converted) converted.textContent = stats.converted || 0;
            if (saved) saved.textContent = formatBytes(stats.bytes_saved || 0);
        })
        .catch(function () {});
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        var k = 1024;
        var sizes = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    loadStats();

    var tabsLoaded = {};

    $$('.ago-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var tabName = this.getAttribute('data-tab');

            $$('.ago-tab').forEach(function (t) { t.classList.remove('active'); });
            this.classList.add('active');

            $$('.ago-tab-content').forEach(function (p) { p.classList.remove('active'); });
            var panel = $('#ago-panel-' + tabName);
            if (panel) panel.classList.add('active');

            if (!tabsLoaded[tabName]) {
                loadAudit(tabName);
            }
        });
    });

    loadAudit('missing-alt');

    function loadAudit(tab) {
        var endpoint = '/audit/' + tab;
        var panel    = $('#ago-panel-' + tab);
        if (!panel) return;

        var loading = panel.querySelector('.ago-audit-loading');
        var table   = panel.querySelector('.ago-audit-table');
        var empty   = panel.querySelector('.ago-audit-empty');
        var dupList = panel.querySelector('.ago-duplicates-list');

        if (loading) loading.style.display = 'block';
        if (table) table.style.display = 'none';
        if (empty) empty.style.display = 'none';
        if (dupList) dupList.style.display = 'none';

        fetch(restUrl + endpoint, {
            headers: { 'X-WP-Nonce': nonce },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            tabsLoaded[tab] = true;
            if (loading) loading.style.display = 'none';

            if (tab === 'duplicates') {
                renderDuplicates(panel, data);
            } else if (tab === 'non-webp') {
                renderNonWebp(panel, data);
            } else {
                renderAuditTable(panel, data, tab);
            }
        })
        .catch(function () {
            if (loading) loading.textContent = t.loadFailed;
        });
    }

    function renderAuditTable(panel, items, tab) {
        var table = panel.querySelector('.ago-audit-table');
        var empty = panel.querySelector('.ago-audit-empty');
        var countEl = tab === 'missing-alt' ? $('#ago-count-alt') : $('#ago-count-orphaned');

        if (countEl) countEl.textContent = items.length;

        if (!items.length) {
            if (empty) empty.style.display = 'block';
            return;
        }

        var tbody = table.querySelector('tbody');
        tbody.innerHTML = '';

        items.forEach(function (item) {
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + thumbCell(item) + '</td>' +
                '<td>' + escHtml(item.title) + '</td>' +
                '<td>' + item.id + '</td>' +
                '<td><a href="' + escHtml(item.edit_url) + '" target="_blank">Edit</a></td>';
            tbody.appendChild(tr);
        });

        table.style.display = 'table';
    }

    function renderDuplicates(panel, groups) {
        var dupList = panel.querySelector('.ago-duplicates-list');
        var empty   = panel.querySelector('.ago-audit-empty');
        var countEl = $('#ago-count-duplicates');

        var keys = Object.keys(groups);
        if (countEl) countEl.textContent = keys.length;

        if (!keys.length) {
            if (empty) empty.style.display = 'block';
            return;
        }

        dupList.innerHTML = '';

        keys.forEach(function (filename) {
            var items = groups[filename];
            var div = document.createElement('div');
            div.className = 'ago-dup-group';

            var html = '<h4>' + escHtml(filename) + ' (' + items.length + ' copies)</h4><div class="ago-dup-items">';
            items.forEach(function (item) {
                html += '<div class="ago-dup-item">' +
                    thumbCell(item) +
                    '<span>ID: ' + item.id + '</span>' +
                    '<a href="' + escHtml(item.edit_url) + '" target="_blank">Edit</a>' +
                    '</div>';
            });
            html += '</div>';

            div.innerHTML = html;
            dupList.appendChild(div);
        });

        dupList.style.display = 'flex';
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function renderNonWebp(panel, data) {
        var items   = data.items || [];
        var table   = panel.querySelector('.ago-audit-table');
        var empty   = panel.querySelector('.ago-audit-empty');
        var actions = panel.querySelector('.ago-optimize-actions');
        var countEl = $('#ago-count-nonwebp');

        if (countEl) countEl.textContent = items.length;

        if (!items.length) {
            if (empty) empty.style.display = 'block';
            return;
        }

        if (actions) actions.style.display = 'flex';

        var tbody = table.querySelector('tbody');
        tbody.innerHTML = '';

        items.forEach(function (item) {
            var tr = document.createElement('tr');
            tr.dataset.id = item.id;
            tr.innerHTML =
                '<td><input type="checkbox" class="ago-opt-check" value="' + item.id + '" checked></td>' +
                '<td>' + thumbCell({ thumbnail_url: item.thumbnail }) + '</td>' +
                '<td>' + escHtml(item.title) + '</td>' +
                '<td>' + escHtml(item.mime) + '</td>' +
                '<td>' + escHtml(item.size_human) + '</td>' +
                '<td class="ago-opt-status"></td>';
            tbody.appendChild(tr);
        });

        table.style.display = 'table';

        var selectAll = $('#ago-select-all-webp');
        if (selectAll) {
            selectAll.checked = true;
            selectAll.addEventListener('change', function () {
                var checks = panel.querySelectorAll('.ago-opt-check');
                checks.forEach(function (cb) { cb.checked = selectAll.checked; });
            });
        }

        var optimizeBtn = $('#ago-optimize-selected');
        if (optimizeBtn) {
            optimizeBtn.addEventListener('click', function () {
                var ids = [];
                panel.querySelectorAll('.ago-opt-check:checked').forEach(function (cb) {
                    ids.push(parseInt(cb.value, 10));
                });
                if (!ids.length) return;
                runBulkOptimize(ids, panel);
            });
        }
    }

    function runBulkOptimize(ids, panel) {
        var btn = $('#ago-optimize-selected');
        var progress = $('#ago-optimize-progress');
        var total = ids.length;
        var done = 0;
        var totalSaved = 0;

        if (btn) { btn.disabled = true; btn.textContent = t.optimizing; }
        if (progress) progress.textContent = '0 / ' + total;

        var queue = ids.slice();
        var batchSize = 3;

        function processBatch() {
            if (!queue.length) {
                if (btn) { btn.disabled = false; btn.textContent = t.optimizeBtn; }
                if (progress) progress.textContent = fill(t.optimizeDone, formatBytes(totalSaved));
                loadStats();
                return;
            }

            var batch = queue.splice(0, batchSize);

            fetch(restUrl + '/optimize', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce,
                },
                body: JSON.stringify({ ids: batch }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var results = data.results || [];
                results.forEach(function (res) {
                    done++;
                    totalSaved += res.saved || 0;
                    var row = panel.querySelector('tr[data-id="' + res.id + '"]');
                    if (row) {
                        var status = row.querySelector('.ago-opt-status');
                        if (status) {
                            status.textContent = res.ok ? res.msg : t.errorLabel + ': ' + res.msg;
                            status.style.color = res.ok ? '#00a32a' : '#d63638';
                        }
                    }
                });
                if (progress) progress.textContent = done + ' / ' + total;
                processBatch();
            })
            .catch(function (err) {
                if (progress) progress.textContent = t.errorLabel + ': ' + err.message;
                if (btn) { btn.disabled = false; btn.textContent = t.optimizeBtn; }
            });
        }

        processBatch();
    }

})();
