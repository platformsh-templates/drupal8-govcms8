/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _iterableToArrayLimit(arr, i) { var _i = arr == null ? null : typeof Symbol !== "undefined" && arr[Symbol.iterator] || arr["@@iterator"]; if (_i == null) return; var _arr = []; var _n = true; var _d = false; var _s, _e; try { for (_i = _i.call(arr); !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

(function (Drupal, drupalSettings, $, JSON, once, Sortable, _ref) {
  var tabbable = _ref.tabbable;
  var toolbarHelp = [{
    message: Drupal.t("The toolbar buttons that don't fit the user's browser window width will be grouped in a dropdown. If multiple toolbar rows are preferred, those can be configured by adding an explicit wrapping breakpoint wherever you want to start a new row.", null, {
      context: 'CKEditor 5 toolbar help text, default, no explicit wrapping breakpoint'
    }),
    button: 'wrapping',
    condition: false
  }, {
    message: Drupal.t('You have configured a multi-row toolbar by using an explicit wrapping breakpoint. This may not work well in narrow browser windows. To use automatic grouping, remove any of these divider buttons.', null, {
      context: 'CKEditor 5 toolbar help text, with explicit wrapping breakpoint'
    }),
    button: 'wrapping',
    condition: true
  }];

  var Observable = function () {
    function Observable(value) {
      _classCallCheck(this, Observable);

      this._listeners = [];
      this._value = value;
    }

    _createClass(Observable, [{
      key: "notify",
      value: function notify() {
        var _this = this;

        this._listeners.forEach(function (listener) {
          return listener(_this._value);
        });
      }
    }, {
      key: "subscribe",
      value: function subscribe(listener) {
        this._listeners.push(listener);
      }
    }, {
      key: "value",
      get: function get() {
        return this._value;
      },
      set: function set(val) {
        if (val !== this._value) {
          this._value = val;
          this.notify();
        }
      }
    }]);

    return Observable;
  }();

  var getSelectedButtons = function getSelectedButtons(selected, dividers, available) {
    return selected.map(function (id) {
      return _objectSpread({}, [].concat(_toConsumableArray(dividers), _toConsumableArray(available)).find(function (button) {
        return button.id === id;
      }));
    });
  };

  var updateSelectedButtons = function updateSelectedButtons(selection, textarea) {
    var newValue = JSON.stringify(selection);
    var priorValue = textarea.innerHTML;
    textarea.value = newValue;
    textarea.innerHTML = newValue;
    textarea.dispatchEvent(new CustomEvent('change', {
      detail: {
        priorValue: priorValue
      }
    }));
  };

  var addToSelectedButtons = function addToSelectedButtons(selection, element, announceChange) {
    var list = _toConsumableArray(selection.value);

    list.push(element.dataset.id);
    selection.value = list;

    if (announceChange) {
      setTimeout(function () {
        announceChange(element.dataset.label);
      });
    }
  };

  var removeFromSelectedButtons = function removeFromSelectedButtons(selection, element, announceChange) {
    var list = _toConsumableArray(selection.value);

    var index = Array.from(element.parentElement.children).findIndex(function (child) {
      return child === element;
    });
    list.splice(index, 1);
    selection.value = list;

    if (announceChange) {
      setTimeout(function () {
        announceChange(element.dataset.label);
      });
    }
  };

  var moveWithinSelectedButtons = function moveWithinSelectedButtons(selection, element, dir) {
    var list = _toConsumableArray(selection.value);

    var index = Array.from(element.parentElement.children).findIndex(function (child) {
      return child === element;
    });
    var condition = dir < 0 ? index > 0 : index < list.length - 1;

    if (condition) {
      list.splice(index + dir, 0, list.splice(index, 1)[0]);
      selection.value = list;
    }
  };

  var copyToActiveButtons = function copyToActiveButtons(selection, element, announceChange) {
    var list = _toConsumableArray(selection.value);

    list.push(element.dataset.id);
    selection.value = list;
    setTimeout(function () {
      if (announceChange) {
        announceChange(element.dataset.label);
      }
    });
  };

  var render = function render(root, selectedButtons, availableButtons, dividerButtons) {
    var toolbarHelpText = toolbarHelp.filter(function (helpItem) {
      return selectedButtons.value.includes(helpItem.button) === helpItem.condition;
    }).map(function (helpItem) {
      return helpItem.message;
    });
    root.innerHTML = Drupal.theme.ckeditor5Admin({
      availableButtons: Drupal.theme.ckeditor5AvailableButtons({
        buttons: availableButtons.filter(function (button) {
          return !selectedButtons.value.includes(button.id);
        })
      }),
      dividerButtons: Drupal.theme.ckeditor5DividerButtons({
        buttons: dividerButtons
      }),
      activeToolbar: Drupal.theme.ckeditor5SelectedButtons({
        buttons: getSelectedButtons(selectedButtons.value, dividerButtons, availableButtons)
      }),
      helpMessage: toolbarHelpText
    });
    new Sortable(root.querySelector('[data-button-list="ckeditor5-toolbar-active-buttons"]'), {
      group: {
        name: 'toolbar',
        put: ['divider', 'available']
      },
      sort: true,
      store: {
        set: function set(sortable) {
          selectedButtons.value = sortable.toArray();
        }
      }
    });
    var toolbarAvailableButtons = new Sortable(root.querySelector('[data-button-list="ckeditor5-toolbar-available-buttons"]'), {
      group: {
        name: 'available',
        put: ['toolbar']
      },
      sort: false,
      onAdd: function onAdd(event) {
        if (dividerButtons.find(function (dividerButton) {
          return dividerButton.id === event.item.dataset.id;
        })) {
          var newIndex = event.newIndex;
          setTimeout(function () {
            document.querySelectorAll('.ckeditor5-toolbar-available__buttons li')[newIndex].remove();
          });
        }
      }
    });
    new Sortable(root.querySelector('[data-button-list="ckeditor5-toolbar-divider-buttons"]'), {
      group: {
        name: 'divider',
        put: false,
        pull: 'clone',
        sort: 'false'
      }
    });
    root.querySelectorAll('[data-drupal-selector="ckeditor5-toolbar-button"]').forEach(function (element) {
      var expandButton = function expandButton(event) {
        event.currentTarget.querySelectorAll('.ckeditor5-toolbar-button').forEach(function (buttonElement) {
          buttonElement.setAttribute('data-expanded', true);
        });
      };

      var retractButton = function retractButton(event) {
        event.currentTarget.querySelectorAll('.ckeditor5-toolbar-button').forEach(function (buttonElement) {
          buttonElement.setAttribute('data-expanded', false);
        });
      };

      element.addEventListener('mouseenter', expandButton);
      element.addEventListener('focus', expandButton);
      element.addEventListener('mouseleave', retractButton);
      element.addEventListener('blur', retractButton);
      element.addEventListener('keyup', function (event) {
        var supportedKeys = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];
        var dir = document.documentElement.dir;

        if (supportedKeys.includes(event.key)) {
          if (event.currentTarget.dataset.divider.toLowerCase() === 'true') {
            switch (event.key) {
              case 'ArrowDown':
                {
                  var announceChange = function announceChange(name) {
                    Drupal.announce(Drupal.t('Button @name has been copied to the active toolbar.', {
                      '@name': name
                    }));
                  };

                  copyToActiveButtons(selectedButtons, event.currentTarget, announceChange);
                  root.querySelector('[data-button-list="ckeditor5-toolbar-active-buttons"] li:last-child').focus();
                  break;
                }
            }
          } else if (selectedButtons.value.includes(event.currentTarget.dataset.id)) {
            var index = Array.from(element.parentElement.children).findIndex(function (child) {
              return child === element;
            });

            switch (event.key) {
              case 'ArrowLeft':
                {
                  var leftOffset = dir === 'ltr' ? -1 : 1;
                  moveWithinSelectedButtons(selectedButtons, event.currentTarget, leftOffset);
                  root.querySelectorAll('[data-button-list="ckeditor5-toolbar-active-buttons"] li')[index + leftOffset].focus();
                  break;
                }

              case 'ArrowRight':
                {
                  var rightOffset = dir === 'ltr' ? 1 : -1;
                  moveWithinSelectedButtons(selectedButtons, event.currentTarget, rightOffset);
                  root.querySelectorAll('[data-button-list="ckeditor5-toolbar-active-buttons"] li')[index + rightOffset].focus();
                  break;
                }

              case 'ArrowUp':
                {
                  var _announceChange = function _announceChange(name) {
                    Drupal.announce(Drupal.t('Button @name has been removed from the active toolbar.', {
                      '@name': name
                    }));
                  };

                  removeFromSelectedButtons(selectedButtons, event.currentTarget, _announceChange);

                  if (!dividerButtons.find(function (dividerButton) {
                    return event.currentTarget.dataset.id === dividerButton.id;
                  })) {
                    root.querySelector("[data-button-list=\"ckeditor5-toolbar-available-buttons\"] [data-id=\"".concat(event.currentTarget.dataset.id, "\"]")).focus();
                  }

                  break;
                }
            }
          } else if (toolbarAvailableButtons.toArray().includes(event.currentTarget.dataset.id)) {
            switch (event.key) {
              case 'ArrowDown':
                {
                  var _announceChange2 = function _announceChange2(name) {
                    Drupal.announce(Drupal.t('Button @name has been moved to the active toolbar.', {
                      '@name': name
                    }));
                  };

                  addToSelectedButtons(selectedButtons, event.currentTarget, _announceChange2);
                  root.querySelector('[data-button-list="ckeditor5-toolbar-active-buttons"] li:last-child').focus();
                  break;
                }
            }
          }
        }
      });
    });
  };

  Drupal.behaviors.ckeditor5Admin = {
    attach: function attach(context) {
      once('ckeditor5-admin-toolbar', '#ckeditor5-toolbar-app').forEach(function (container) {
        var selectedTextarea = context.querySelector('#ckeditor5-toolbar-buttons-selected');
        var available = Object.entries(JSON.parse(context.querySelector('#ckeditor5-toolbar-buttons-available').innerHTML)).map(function (_ref2) {
          var _ref3 = _slicedToArray(_ref2, 2),
              name = _ref3[0],
              attrs = _ref3[1];

          return _objectSpread({
            name: name,
            id: name
          }, attrs);
        });
        var dividers = [{
          id: 'divider',
          name: '|',
          label: Drupal.t('Divider')
        }, {
          id: 'wrapping',
          name: '-',
          label: Drupal.t('Wrapping')
        }];
        var selected = new Observable(JSON.parse(selectedTextarea.innerHTML).map(function (name) {
          return [].concat(dividers, _toConsumableArray(available)).find(function (button) {
            return button.name === name;
          }).id;
        }));

        var mapSelection = function mapSelection(selection) {
          return selection.map(function (id) {
            return [].concat(dividers, _toConsumableArray(available)).find(function (button) {
              return button.id === id;
            }).name;
          });
        };

        selected.subscribe(function (selection) {
          updateSelectedButtons(mapSelection(selection), selectedTextarea);
          render(container, selected, available, dividers);
        });
        [context.querySelector('#ckeditor5-toolbar-buttons-available'), context.querySelector('[class*="editor-settings-toolbar-items"]')].filter(function (el) {
          return el;
        }).forEach(function (el) {
          el.classList.add('visually-hidden');
        });
        render(container, selected, available, dividers);
      });
      once('safari-focus-fix', '.ckeditor5-toolbar-item').forEach(function (item) {
        item.addEventListener('keydown', function (e) {
          var keyCodeDirections = {
            9: 'tab',
            37: 'left',
            38: 'up',
            39: 'right',
            40: 'down'
          };

          if (['tab', 'left', 'up', 'right', 'down'].includes(keyCodeDirections[e.keyCode])) {
            var hideTip = false;
            var isActive = e.target.closest('[data-button-list="ckeditor5-toolbar-active__buttons"]');

            if (isActive) {
              if (['tab', 'left', 'up', 'right'].includes(keyCodeDirections[e.keyCode])) {
                hideTip = true;
              }
            } else if (['tab', 'down'].includes(keyCodeDirections[e.keyCode])) {
              hideTip = true;
            }

            if (hideTip) {
              e.target.querySelector('[data-expanded]').setAttribute('data-expanded', 'false');
            }
          }
        });
      });

      var updateUiStateStorage = function updateUiStateStorage(states) {
        var form = document.querySelector('#filter-format-edit-form, #filter-format-add-form');
        var currentStates = form.hasAttribute('data-drupal-ui-state') ? JSON.parse(form.getAttribute('data-drupal-ui-state')) : {};
        form.setAttribute('data-drupal-ui-state', JSON.stringify(_objectSpread(_objectSpread({}, currentStates), states)));
      };

      var getUiStateStorage = function getUiStateStorage(property) {
        var form = document.querySelector('#filter-format-edit-form, #filter-format-add-form');

        if (form === null) {
          return;
        }

        return form.hasAttribute('data-drupal-ui-state') ? JSON.parse(form.getAttribute('data-drupal-ui-state'))[property] : null;
      };

      once('ui-state-storage', '#filter-format-edit-form, #filter-format-add-form').forEach(function (form) {
        form.setAttribute('data-drupal-ui-state', JSON.stringify({}));
      });

      var maintainActiveVerticalTab = function maintainActiveVerticalTab(verticalTabs) {
        var id = verticalTabs.id;
        var activeTab = getUiStateStorage("".concat(id, "-active-tab"));

        if (activeTab) {
          setTimeout(function () {
            var activeTabLink = document.querySelector(activeTab);
            activeTabLink.click();

            if (id !== 'plugin-settings-wrapper') {
              return;
            }

            if (document.activeElement !== document.body) {
              return;
            }

            var targetTabPane = document.querySelector(activeTabLink.getAttribute('href'));

            if (targetTabPane) {
              var tabbableElements = tabbable(targetTabPane);

              if (tabbableElements.length) {
                tabbableElements[0].focus();
              }
            }
          });
        }

        verticalTabs.querySelectorAll('.vertical-tabs__menu').forEach(function (tab) {
          tab.addEventListener('click', function (e) {
            var state = {};
            var href = e.target.closest('[href]').getAttribute('href').split('--')[0];
            state["".concat(id, "-active-tab")] = "#".concat(id, " [href^='").concat(href, "']");
            updateUiStateStorage(state);
          });
        });
      };

      once('maintainActiveVerticalTab', '#plugin-settings-wrapper, #filter-settings-wrapper').forEach(maintainActiveVerticalTab);
      var selectedButtons = document.querySelector('#ckeditor5-toolbar-buttons-selected');
      once('textarea-listener', selectedButtons).forEach(function (textarea) {
        textarea.addEventListener('change', function (e) {
          var buttonName = document.activeElement.getAttribute('data-id');

          if (!buttonName) {
            return;
          }

          var focusSelector = '';

          if (['divider', 'wrapping'].includes(buttonName)) {
            var oldConfig = JSON.parse(e.detail.priorValue);
            var newConfig = JSON.parse(e.target.innerHTML);

            if (oldConfig.length > newConfig.length) {
              for (var item = 0; item < newConfig.length; item++) {
                if (newConfig[item] !== oldConfig[item]) {
                  focusSelector = "[data-button-list=\"ckeditor5-toolbar-active-buttons\"] li:nth-child(".concat(Math.min(item - 1, 0), ")");
                  break;
                }
              }
            } else if (oldConfig.length < newConfig.length) {
              focusSelector = '[data-button-list="ckeditor5-toolbar-active-buttons"] li:last-child';
            } else {
              document.querySelectorAll("[data-button-list=\"ckeditor5-toolbar-active-buttons\"] [data-id='".concat(buttonName, "']")).forEach(function (divider, index) {
                if (divider === document.activeElement) {
                  focusSelector = "".concat(buttonName, "|").concat(index);
                }
              });
            }
          } else {
            focusSelector = "[data-id='".concat(buttonName, "']");
          }

          updateUiStateStorage({
            focusSelector: focusSelector
          });
        });
        textarea.addEventListener('focus', function () {
          var focusSelector = getUiStateStorage('focusSelector');

          if (focusSelector) {
            if (focusSelector.includes('|')) {
              var _focusSelector$split = focusSelector.split('|'),
                  _focusSelector$split2 = _slicedToArray(_focusSelector$split, 2),
                  buttonName = _focusSelector$split2[0],
                  count = _focusSelector$split2[1];

              document.querySelectorAll("[data-button-list=\"ckeditor5-toolbar-active-buttons\"] [data-id='".concat(buttonName, "']")).forEach(function (item, index) {
                if (index === parseInt(count, 10)) {
                  item.focus();
                }
              });
            } else {
              var toFocus = document.querySelector(focusSelector);

              if (toFocus) {
                toFocus.focus();
              }
            }
          }
        });
      });
    }
  };

  Drupal.theme.ckeditor5SelectedButtons = function (_ref4) {
    var buttons = _ref4.buttons;
    return "\n      <ul class=\"ckeditor5-toolbar-tray ckeditor5-toolbar-active__buttons\" data-button-list=\"ckeditor5-toolbar-active-buttons\" role=\"listbox\" aria-orientation=\"horizontal\" aria-labelledby=\"ckeditor5-toolbar-active-buttons-label\">\n        ".concat(buttons.map(function (button) {
      return Drupal.theme.ckeditor5Button({
        button: button,
        listType: 'active'
      });
    }).join(''), "\n      </ul>\n    ");
  };

  Drupal.theme.ckeditor5DividerButtons = function (_ref5) {
    var buttons = _ref5.buttons;
    return "\n      <ul class=\"ckeditor5-toolbar-tray ckeditor5-toolbar-divider__buttons\" data-button-list=\"ckeditor5-toolbar-divider-buttons\" role=\"listbox\" aria-orientation=\"horizontal\" aria-labelledby=\"ckeditor5-toolbar-divider-buttons-label\">\n        ".concat(buttons.map(function (button) {
      return Drupal.theme.ckeditor5Button({
        button: button,
        listType: 'divider'
      });
    }).join(''), "\n      </ul>\n    ");
  };

  Drupal.theme.ckeditor5AvailableButtons = function (_ref6) {
    var buttons = _ref6.buttons;
    return "\n      <ul class=\"ckeditor5-toolbar-tray ckeditor5-toolbar-available__buttons\" data-button-list=\"ckeditor5-toolbar-available-buttons\" role=\"listbox\" aria-orientation=\"horizontal\" aria-labelledby=\"ckeditor5-toolbar-available-buttons-label\">\n        ".concat(buttons.map(function (button) {
      return Drupal.theme.ckeditor5Button({
        button: button,
        listType: 'available'
      });
    }).join(''), "\n      </ul>\n    ");
  };

  Drupal.theme.ckeditor5Button = function (_ref7) {
    var _ref7$button = _ref7.button,
        label = _ref7$button.label,
        id = _ref7$button.id,
        listType = _ref7.listType;
    var visuallyHiddenLabel = Drupal.t("@listType button @label", {
      '@listType': listType !== 'divider' ? listType : 'available',
      '@label': label
    });
    return "\n      <li class=\"ckeditor5-toolbar-item ckeditor5-toolbar-item-".concat(id, "\" role=\"option\" tabindex=\"0\" data-drupal-selector=\"ckeditor5-toolbar-button\" data-id=\"").concat(id, "\" data-label=\"").concat(label, "\" data-divider=\"").concat(listType === 'divider', "\">\n        <span class=\"ckeditor5-toolbar-button ckeditor5-toolbar-button-").concat(id, "\">\n          <span class=\"visually-hidden\">").concat(visuallyHiddenLabel, "</span>\n        </span>\n        <span class=\"ckeditor5-toolbar-tooltip\" aria-hidden=\"true\">").concat(label, "</span>\n      </li>\n    ");
  };

  Drupal.theme.ckeditor5Admin = function (_ref8) {
    var availableButtons = _ref8.availableButtons,
        dividerButtons = _ref8.dividerButtons,
        activeToolbar = _ref8.activeToolbar,
        helpMessage = _ref8.helpMessage;
    return "\n    <div aria-live=\"polite\" data-drupal-selector=\"ckeditor5-admin-help-message\">\n      <p>".concat(helpMessage.join('</p><p>'), "</p>\n    </div>\n    <div class=\"ckeditor5-toolbar-disabled\">\n      <div class=\"ckeditor5-toolbar-available\">\n        <label id=\"ckeditor5-toolbar-available-buttons-label\">").concat(Drupal.t('Available buttons'), "</label>\n        ").concat(availableButtons, "\n      </div>\n      <div class=\"ckeditor5-toolbar-divider\">\n        <label id=\"ckeditor5-toolbar-divider-buttons-label\">").concat(Drupal.t('Button divider'), "</label>\n        ").concat(dividerButtons, "\n      </div>\n    </div>\n    <div class=\"ckeditor5-toolbar-active\">\n      <label id=\"ckeditor5-toolbar-active-buttons-label\">").concat(Drupal.t('Active toolbar'), "</label>\n      ").concat(activeToolbar, "\n    </div>\n    ");
  };

  var originalFilterStatusAttach = Drupal.behaviors.filterStatus.attach;

  Drupal.behaviors.filterStatus.attach = function (context, settings) {
    var filterStatusCheckboxes = document.querySelectorAll('#filters-status-wrapper input.form-checkbox');
    once.remove('filter-status', filterStatusCheckboxes);
    $(filterStatusCheckboxes).off('click.filterUpdate');
    originalFilterStatusAttach(context, settings);
  };

  Drupal.behaviors.tabErrorsVisible = {
    attach: function attach(context) {
      context.querySelectorAll('details .form-item .error').forEach(function (item) {
        var details = item.closest('details');

        if (details.style.display === 'none') {
          var tabSelect = document.querySelector("[href='#".concat(details.id, "']"));

          if (tabSelect) {
            tabSelect.click();
          }
        }
      });
    }
  };
})(Drupal, drupalSettings, jQuery, JSON, once, Sortable, tabbable);