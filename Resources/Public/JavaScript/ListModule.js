/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: TYPO3/CMS/Devlog/ListModule
 * Devlog "List" module JS
 */

// The "mark.js" plugin for DataTables expects DataTables to be loaded as "datatables.net" when used as a
// module. Since the TYPO3 Core uses "datatables", create an alias called "datatables.net".
require.config({
	map: {
		'*': {
			'datatables.net': 'datatables'
		}
	}
});

define(['jquery',
		'moment',
		'TYPO3/CMS/Backend/Icons',
		'datatables.net',
		'TYPO3/CMS/Backend/jquery.clearable',
		'./jquery.mark.min',
		'./datatables.mark.min'
	   ], function($, moment, Icons) {
	'use strict';

	var DevlogListModule = {
		table: null,
		severityIcons: {},
		expandIcon: null,
		collapseIcon: null,
		listWrapper: null,
		loadingMask: null,
		lastUpdateTime: null,
		intervalID: 0
	};

	DevlogListModule.init = function() {
		this.loadingMask = $('#tx_devlog_list_loader');
		this.listWrapper = $('#tx_devlog_list_wrapper');
	};

	/**
	 * Preloads all necessary icons.
	 */
	DevlogListModule.loadIcons = function() {
		// Severity icons
		Icons.getIcon('status-dialog-ok', Icons.sizes.small, '', '').done(function(markup) {
			DevlogListModule.severityIcons[-1] = markup;
		});
		Icons.getIcon('status-dialog-information', Icons.sizes.small, '', '').done(function(markup) {
			DevlogListModule.severityIcons[0] = markup;
		});
		Icons.getIcon('status-dialog-notification', Icons.sizes.small, '', '').done(function(markup) {
			DevlogListModule.severityIcons[1] = markup;
		});
		Icons.getIcon('status-dialog-warning', Icons.sizes.small, '', '').done(function(markup) {
			DevlogListModule.severityIcons[2] = markup;
		});
		Icons.getIcon('status-dialog-error', Icons.sizes.small, '', '').done(function(markup) {
			DevlogListModule.severityIcons[3] = markup;
		});
		// Expand and collapse
		Icons.getIcon('actions-view-list-expand', Icons.sizes.small, '', '').done(function(markup) {
			DevlogListModule.expandIcon = markup;
		});
		Icons.getIcon('actions-view-list-collapse', Icons.sizes.small, '', '').done(function(markup) {
			DevlogListModule.collapseIcon = markup;
		});
	};

	/**
	 * Loads log data dynamically and initializes DataTables.
	 *
	 * @param tableView
	 */
	DevlogListModule.buildDynamicTable = function(tableView) {
		this.lastUpdateTime = moment().unix();
		$.ajax({
			url: TYPO3.settings.ajaxUrls['tx_devlog_list'],
			success: function (data, status, xhr) {
				DevlogListModule.table = tableView.DataTable({
					data: data,
					dom: 'tp',
					// Default ordering is "crdate" column
					order: [
						[0, 'desc']
					],
					mark: true,
					columnDefs: [
						{
							targets: 'entry-date',
							data: 'crdate',
							render:  function(data, type, row, meta) {
								if (type === 'display' || type === 'filter') {
									var date = moment.unix(data);
									return date.format('YYYY-MM-DD HH:mm:ss');
								} else {
									// Add timestamp and sorting to get fine ordering (making sure we have integers)
									return parseInt(data * 1000) + parseInt(row.sorting);
								}
							}
						},
						{
							targets: 'entry-severity',
							data: 'severity',
							render:  function(data, type, row, meta) {
								if (type === 'display') {
									return DevlogListModule.severityIcons[data];
								} else {
									return data;
								}
							}
						},
						{
							targets: 'entry-extension',
							data: 'extkey'
						},
						{
							targets: 'entry-message',
							data: 'message'
						},
						{
							targets: 'entry-location',
							data: 'location',
							render:  function(data, type, row, meta) {
								return data + ', line ' + row.line;
							}
						},
						{
							targets: 'entry-ip',
							data: 'ip'
						},
						{
							targets: 'entry-page',
							data: 'page'
						},
						{
							targets: 'entry-user',
							data: 'username'
						},
						{
							targets: 'entry-data',
							data: 'extra_data',
							orderable: false,
							render:  function(data, type, row, meta) {
								if (type === 'display') {
									var html = '';
									if (data !== '') {
										html += '<button class="btn btn-default extra-data-toggle">';
										html += DevlogListModule.expandIcon;
										html += '</button>';
										html += '<div class="extra-data-wrapper" style="display: none">';
										html += '<pre>' + data + '</pre>';
										html += '</div>';
									}
									return html;
								} else {
									return data;
								}
							}
						}
					],
					initComplete: function() {
						DevlogListModule.initializeSearchField();
						DevlogListModule.initializeExtraDataToggle(tableView);
						DevlogListModule.initializeReloadControls();
						DevlogListModule.toggleLoadingMask();
					}
				});
			}
		});
	};

	/**
	 * Initializes the search field (make it clearable and reactive to input).
	 */
	DevlogListModule.initializeSearchField = function() {
		$('#tx_devlog_search')
			.on('input', function() {
				DevlogListModule.table.search($(this).val()).draw();
			})
			.clearable({
				onClear: function() {
					if (DevlogListModule.table !== null) {
						DevlogListModule.table.search('').draw();
					}
				}
			})
			.parents('form').on('submit', function() {
				return false;
			});
	};

	/**
	 * Initializes the extra data toggle buttons.
	 *
	 * @param tableView
	 */
	DevlogListModule.initializeExtraDataToggle = function(tableView) {
		// Single toggle button
		tableView.on('click', 'button.extra-data-toggle', function() {
			DevlogListModule.toggleExtraData($(this));
		});
		// Global toggle button
		tableView.on('click', '#tx_devlog_expand_all', function() {
			var toggleIcon = $('#tx_devlog_expand_all_icon');
			// Switch expand/collapse icon for global toggle
			if (toggleIcon.find('.t3js-icon').hasClass('icon-actions-view-list-expand')) {
				toggleIcon.html(DevlogListModule.collapseIcon);
			} else {
				toggleIcon.html(DevlogListModule.expandIcon);
			}
			// Loop on each individual toggle
			$('button.extra-data-toggle').each(function() {
				DevlogListModule.toggleExtraData($(this));
			});
		});
	};

	/**
	 * Toggles extra data visibility for a given button.
	 *
	 * @param button
	 */
	DevlogListModule.toggleExtraData = function(button) {
		var extraDataWrapper = button.next('.extra-data-wrapper');
		// Change visibility of extra data wrapper
		extraDataWrapper.toggle();
		// Switch expand/collapse icon
		if (extraDataWrapper.is(':visible')) {
			button.html(DevlogListModule.collapseIcon);
		} else {
			button.html(DevlogListModule.expandIcon);
		}
		button.blur();
	};

	/**
	 * Initializes the controls for manual reloading or automatic reloading
	 * of the table to read new records created since last update.
	 */
	DevlogListModule.initializeReloadControls = function () {
		// Handle reload action
		$('#tx_devlog_reload').on('click', function () {
			DevlogListModule.loadNewRecords();
		});
		// Handle automatic reloading
		$('#tx_devlog_autoreload').on('click', function () {
			// If no interval exists yet, activate reload automation
			if (DevlogListModule.intervalID === 0) {
				DevlogListModule.intervalID = window.setInterval(
					DevlogListModule.loadNewRecords,
					TYPO3.settings.DevLog.refreshFrequency * 1000
				);

			// If an interval already exists, clear it
			} else {
				window.clearInterval(DevlogListModule.intervalID);
				DevlogListModule.intervalID = 0;
			}
		});
	};

	/**
	 * Loads records created since last update and refreshes table view.
	 */
	DevlogListModule.loadNewRecords = function () {
		// Activate loading mask
		DevlogListModule.toggleLoadingMask();
		// Fetch new records
		$.ajax({
			url: TYPO3.settings.ajaxUrls['tx_devlog_reload'],
			data: {
				timestamp: DevlogListModule.lastUpdateTime
			},
			success: function (data, status, xhr) {
				// Update last update time
				DevlogListModule.lastUpdateTime = moment().unix();
				// Add records to DataTable
				DevlogListModule.table.rows.add(data).draw();
			},
			complete: function (xhr, status) {
				// Restore table view
				DevlogListModule.toggleLoadingMask();
			}
		});
	};

	/**
	 * Toggles visibility of loading mask and table view
	 */
	DevlogListModule.toggleLoadingMask = function() {
		if (this.listWrapper.hasClass('hidden')) {
			this.listWrapper.removeClass('hidden');
			this.loadingMask.addClass('hidden');
		} else {
			this.listWrapper.addClass('hidden');
			this.loadingMask.removeClass('hidden');
		}

	};

	/**
	 * Initializes this module
	 */
	$(function() {
		var tableView = $('#tx_devlog_list');
		DevlogListModule.init();
		// @todo: how to ensure that all icons have been loaded? Deliver a new promise?
		DevlogListModule.loadIcons();
		DevlogListModule.buildDynamicTable(tableView);
	});

	return DevlogListModule;
});

