(function ($) {
	'use strict';

	var scanning = false;
	var timer = null;

	function post(action, data) {
		data = data || {};
		data.action = action;
		data.nonce = wpsmsAdmin.nonce;
		return $.post(wpsmsAdmin.ajaxUrl, data);
	}

	function setProgress(job) {
		var pct = job && job.progress ? parseInt(job.progress, 10) : 0;
		$('#wpsms-progress-bar').css('width', pct + '%');
		if (job) {
			$('#wpsms-status-text').text(
				'Status: ' + (job.status || 'idle') + ' — phase: ' + (job.phase || '—') +
				(job.files_scanned ? (' — files: ' + job.files_scanned + '/' + (job.total_files || '?')) : '')
			);
		}
	}

	function applyCounts(payload) {
		var counts = payload.counts || {};
		var last = payload.last || {};
		if (last.finished_at) {
			var d = new Date(last.finished_at * 1000);
			$('#wpsms-last-scan').text(isNaN(d.getTime()) ? '—' : d.toISOString().replace('T', ' ').slice(0, 19));
		}
		if (typeof last.files_scanned !== 'undefined') {
			$('#wpsms-files-scanned').text(last.files_scanned);
		} else if (payload.job && payload.job.files_scanned) {
			$('#wpsms-files-scanned').text(payload.job.files_scanned);
		}
		$('#wpsms-total').text(counts.total || 0);
		$('#wpsms-critical').text(counts.critical || 0);
		$('#wpsms-high').text(counts.high || 0);
		$('#wpsms-medium').text(counts.medium || 0);
		$('#wpsms-low').text((counts.low || 0) + (counts.info || 0));
	}

	function tick() {
		post('wpsms_scan_tick').done(function (res) {
			if (!res || !res.success) {
				scanning = false;
				return;
			}
			var job = res.data.job || {};
			setProgress(job);
			applyCounts(res.data);
			if (job.status === 'running' || job.status === 'stopping') {
				timer = window.setTimeout(tick, 400);
			} else {
				scanning = false;
				loadFindings();
			}
		}).fail(function () {
			scanning = false;
		});
	}

	function start(mode) {
		if (scanning) {
			return;
		}
		scanning = true;
		post('wpsms_start_scan', { mode: mode }).done(function (res) {
			if (!res || !res.success) {
				scanning = false;
				window.alert((res && res.data && res.data.message) || 'Unable to start scan.');
				return;
			}
			setProgress(res.data.job);
			tick();
		}).fail(function () {
			scanning = false;
		});
	}

	function sevIcon(sev) {
		var map = {
			critical: '🔴 Critical',
			high: '🟠 High',
			medium: '🟡 Medium',
			low: '🔵 Low',
			info: '⚪ Info'
		};
		return map[sev] || sev;
	}

	function filterParams() {
		var filter = $('#wpsms-filter').val() || 'all';
		var severity = 'all';
		var type = 'all';
		if (['critical', 'high', 'medium', 'low'].indexOf(filter) !== -1) {
			severity = filter;
		} else if (filter !== 'all') {
			type = filter;
		}
		return {
			severity: severity,
			type: type,
			search: $('#wpsms-search').val() || ''
		};
	}

	function loadFindings() {
		var $body = $('#wpsms-findings-body');
		if (!$body.length) {
			return;
		}
		var params = filterParams();
		post('wpsms_get_findings', params).done(function (res) {
			if (!res || !res.success) {
				$body.html('<tr><td colspan="6">Unable to load findings.</td></tr>');
				return;
			}
			var rows = res.data.rows || [];
			if (!rows.length) {
				$body.html('<tr><td colspan="6">No findings match this filter.</td></tr>');
				return;
			}
			var html = '';
			rows.forEach(function (row) {
				html += '<tr>';
				html += '<td><span class="wpsms-sev wpsms-sev-' + $('<span>').text(row.severity).html() + '">' + sevIcon(row.severity) + '</span></td>';
				html += '<td>' + $('<span>').text(row.type).html() + '</td>';
				html += '<td><code>' + $('<span>').text(row.location).html() + '</code></td>';
				html += '<td>' + (row.line_number || '—') + '</td>';
				html += '<td>' + $('<span>').text(row.title).html() + '</td>';
				html += '<td class="wpsms-actions">';
				html += '<button type="button" class="button button-small wpsms-open" data-id="' + row.id + '">View</button>';
				html += '</td></tr>';
			});
			$body.html(html);
		});
	}

	function openFinding(id) {
		post('wpsms_get_finding', { id: id }).done(function (res) {
			if (!res || !res.success) {
				return;
			}
			var f = res.data;
			var file = f.location;
			var html = '';
			html += '<h2>' + $('<span>').text(f.title).html() + '</h2>';
			html += '<p><strong>What was detected:</strong> ' + $('<span>').text(f.title).html() + ' (' + $('<span>').text(f.confidence).html() + ')</strong></p>';
			html += '<p><strong>Why it matters:</strong> ' + $('<span>').text(f.why_suspicious || '').html() + '</p>';
			html += '<p><strong>Risk explanation:</strong> ' + $('<span>').text(f.risk_explanation || '').html() + '</p>';
			html += '<p><strong>Exact file:</strong> <code>' + $('<span>').text(f.location).html() + '</code></p>';
			html += '<p><strong>Line:</strong> ' + (f.line_number || '—') + '</p>';
			html += '<p><strong>Pattern:</strong> ' + $('<span>').text(f.matched_pattern || '').html() + '</p>';
			html += '<p><strong>Modified:</strong> ' + (f.file_mtime ? new Date(f.file_mtime * 1000).toISOString() : '—') +
				' &nbsp; <strong>Size:</strong> ' + (f.file_size || '—') +
				' &nbsp; <strong>Perms:</strong> ' + $('<span>').text(f.file_perms || '—').html() +
				' &nbsp; <strong>Owner:</strong> ' + $('<span>').text(f.file_owner || '—').html() + '</p>';
			html += '<p><strong>SHA-256:</strong> <code id="wpsms-hash-val">' + $('<span>').text(f.file_hash || '—').html() + '</code></p>';
			html += '<pre class="wpsms-snippet">' + $('<span>').text(f.snippet || '').html() + '</pre>';
			html += '<p><strong>Recommended next step:</strong> ' + $('<span>').text(f.recommended_action || '').html() + '</p>';
			html += '<p class="wpsms-actions">';
			html += '<button type="button" class="button wpsms-view-file" data-file="' + $('<span>').text(file).html() + '">View file</button> ';
			html += '<a class="button" href="' + wpsmsAdmin.downloadUrl + '&wpsms_download=1&file=' + encodeURIComponent(file) + '&_wpnonce=' + encodeURIComponent(wpsmsAdmin.nonce) + '">Download backup</a> ';
			html += '<button type="button" class="button wpsms-hash" data-file="' + $('<span>').text(file).html() + '">Generate hash</button> ';
			html += '<button type="button" class="button wpsms-quarantine" data-file="' + $('<span>').text(file).html() + '">Quarantine</button> ';
			html += '<button type="button" class="button button-secondary wpsms-delete" data-file="' + $('<span>').text(file).html() + '">Delete</button>';
			html += '</p>';
			html += '<p class="description">Quarantine and delete require confirmation, create a backup, and refuse WordPress core files. Plugin/theme files cannot be deleted from this screen.</p>';
			$('#wpsms-modal-body').html(html);
			$('#wpsms-modal').removeAttr('hidden');
		});
	}

	function exportReport(format) {
		post('wpsms_export_report', { format: format }).done(function (res) {
			if (!res || !res.success) {
				window.alert((res && res.data && res.data.message) || 'Export failed.');
				return;
			}
			var blob = new Blob([res.data.body], { type: res.data.mime || 'text/plain' });
			var a = document.createElement('a');
			a.href = URL.createObjectURL(blob);
			a.download = res.data.filename || 'report.txt';
			a.click();
		});
	}

	$(function () {
		$('#wpsms-full-scan').on('click', function () { start('full'); });
		$('#wpsms-quick-scan').on('click', function () { start('quick'); });
		$('#wpsms-stop-scan').on('click', function () {
			post('wpsms_stop_scan').done(function (res) {
				if (res && res.data) {
					setProgress(res.data.job);
				}
			});
		});
		$('#wpsms-reload-findings, #wpsms-filter').on('change click', loadFindings);
		$('#wpsms-search').on('keyup', function () {
			window.clearTimeout(this._t);
			this._t = window.setTimeout(loadFindings, 300);
		});
		$(document).on('click', '.wpsms-open', function () {
			openFinding($(this).data('id'));
		});
		$(document).on('click', '.wpsms-modal-close', function () {
			$('#wpsms-modal').attr('hidden', 'hidden');
		});
		$(document).on('click', '.wpsms-view-file', function () {
			var file = $(this).data('file');
			post('wpsms_view_file', { file: file }).done(function (res) {
				if (!res || !res.success) {
					window.alert((res && res.data && res.data.message) || 'Unable to open file.');
					return;
				}
				$('#wpsms-modal-body').append('<h3>File contents (redacted)</h3><pre class="wpsms-snippet"></pre>');
				$('#wpsms-modal-body pre.wpsms-snippet').last().text(res.data.content);
			});
		});
		$(document).on('click', '.wpsms-hash', function () {
			var file = $(this).data('file');
			post('wpsms_hash_file', { file: file }).done(function (res) {
				if (!res || !res.success) {
					window.alert((res && res.data && res.data.message) || 'Hash failed.');
					return;
				}
				$('#wpsms-hash-val').text(res.data.hash);
			});
		});
		$(document).on('click', '.wpsms-quarantine', function () {
			if (!window.confirm(wpsmsAdmin.i18n.confirmQuarantine)) {
				return;
			}
			var typed = window.prompt(wpsmsAdmin.i18n.typeConfirm, '');
			if (typed !== 'CONFIRM') {
				return;
			}
			post('wpsms_quarantine', { file: $(this).data('file'), confirm: 'confirm' }).done(function (res) {
				window.alert(res.success ? 'File quarantined. Backup created.' : (res.data && res.data.message) || 'Failed');
			});
		});
		$(document).on('click', '.wpsms-delete', function () {
			if (!window.confirm(wpsmsAdmin.i18n.confirmDelete1)) {
				return;
			}
			var typed = window.prompt(wpsmsAdmin.i18n.typeDelete, '');
			if (typed !== 'DELETE') {
				return;
			}
			post('wpsms_delete_file', { file: $(this).data('file'), confirm: 'confirm', confirm2: 'delete' }).done(function (res) {
				window.alert(res.success ? 'File deleted after backup.' : (res.data && res.data.message) || 'Failed');
			});
		});
		$('#wpsms-report-json').on('click', function () { exportReport('json'); });
		$('#wpsms-report-csv').on('click', function () { exportReport('csv'); });
		$('#wpsms-report-html').on('click', function () { exportReport('html'); });
		if ($('#wpsms-findings-body').length) {
			loadFindings();
		}
	});
})(jQuery);
