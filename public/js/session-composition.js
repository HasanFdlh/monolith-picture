/*!
 * Render ulang hasil akhir sesi photobooth (frame + background + foto pilihan)
 * di dashboard, lengkap dengan pemilih filter. Rumus filter sama persis dengan
 * aplikasi Flutter (lib/models/booth_models.dart: BoothFilterRecipe).
 */
(function (global) {
  'use strict';

  var FILTERS = [{"key": "none", "label": "Natural", "recipe": null, "vig": 0}, {"key": "glow", "label": "Glow", "recipe": {"brightness": 0.05, "contrast": 0.94, "saturation": 1.05, "red": 1.04, "blue": 0.98, "fade": 0.04}, "vig": 0}, {"key": "clear", "label": "Clear", "recipe": {"contrast": 1.18, "saturation": 1.25, "red": 0.98, "blue": 1.06, "brightness": 0.02}, "vig": 0}, {"key": "fresh", "label": "Fresh", "recipe": {"brightness": 0.06, "contrast": 1.05, "saturation": 0.95, "red": 0.97, "green": 1.03, "blue": 1.05}, "vig": 0}, {"key": "sunny", "label": "Sunny", "recipe": {"contrast": 1.1, "saturation": 1.3, "red": 1.08, "blue": 0.92, "brightness": 0.02}, "vig": 0}, {"key": "honey", "label": "Honey", "recipe": {"saturation": 1.05, "red": 1.08, "green": 1.02, "blue": 0.88, "brightness": 0.03, "fade": 0.08}, "vig": 0}, {"key": "golden", "label": "Golden Hour", "recipe": {"saturation": 1.15, "contrast": 1.05, "red": 1.14, "green": 1.04, "blue": 0.82, "brightness": 0.04}, "vig": 0.12}, {"key": "warm", "label": "Warm Glow", "recipe": {"saturation": 1.1, "red": 1.1, "green": 1.01, "blue": 0.9, "brightness": 0.03}, "vig": 0}, {"key": "peach", "label": "Peach", "recipe": {"saturation": 1.1, "contrast": 1.05, "red": 1.08, "green": 0.98, "blue": 0.95, "shiftRed": 14.0, "shiftGreen": 4.0, "shiftBlue": 10.0, "fade": 0.1}, "vig": 0}, {"key": "rose", "label": "Rose", "recipe": {"saturation": 1.1, "contrast": 1.05, "red": 1.06, "green": 0.97, "blue": 1.02, "shiftRed": 8.0, "shiftBlue": 6.0, "brightness": 0.04}, "vig": 0.15}, {"key": "dreamy", "label": "Dreamy", "recipe": {"saturation": 1.1, "contrast": 0.9, "blue": 1.06, "brightness": 0.07, "fade": 0.06}, "vig": 0.15}, {"key": "pastel", "label": "Pastel", "recipe": {"saturation": 0.85, "contrast": 0.85, "red": 1.02, "blue": 1.05, "brightness": 0.08, "fade": 0.08}, "vig": 0}, {"key": "softfade", "label": "Soft Fade", "recipe": {"saturation": 0.9, "contrast": 0.95, "blue": 1.04, "brightness": 0.03, "fade": 0.14}, "vig": 0}, {"key": "cool", "label": "Cool Blue", "recipe": {"red": 0.96, "blue": 1.1, "shiftRed": -3.0, "shiftBlue": 8.0, "brightness": 0.02}, "vig": 0}, {"key": "frost", "label": "Frost", "recipe": {"saturation": 1.05, "contrast": 1.2, "red": 0.92, "blue": 1.15, "shiftBlue": 8.0, "brightness": 0.02}, "vig": 0.25}, {"key": "dusk", "label": "Dusk", "recipe": {"saturation": 0.9, "contrast": 0.92, "red": 1.03, "green": 0.98, "blue": 0.96, "fade": 0.15}, "vig": 0.2}, {"key": "film", "label": "Film", "recipe": {"saturation": 0.88, "contrast": 1.12, "red": 1.03, "green": 1.02, "blue": 0.97, "fade": 0.08}, "vig": 0.2}, {"key": "vintage", "label": "Vintage", "recipe": {"saturation": 0.8, "red": 1.05, "blue": 0.85, "shiftRed": 6.0, "fade": 0.12}, "vig": 0.25}, {"key": "sepia", "label": "Sepia Retro", "recipe": {"saturation": 0.25, "contrast": 1.05, "red": 1.15, "blue": 0.78, "fade": 0.1}, "vig": 0.4}, {"key": "cinema", "label": "Cinema", "recipe": {"saturation": 0.9, "contrast": 1.2, "red": 1.05, "green": 0.98, "blue": 1.04}, "vig": 0.35}, {"key": "xpro", "label": "Cross", "recipe": {"saturation": 1.15, "contrast": 1.25, "red": 1.05, "blue": 0.88, "shiftBlue": 14.0}, "vig": 0.4}, {"key": "vivid", "label": "Vivid", "recipe": {"saturation": 1.4, "contrast": 1.1}, "vig": 0}, {"key": "punch", "label": "Punch", "recipe": {"saturation": 1.4, "contrast": 1.3}, "vig": 0.3}, {"key": "noir", "label": "Noir", "recipe": {"saturation": 0.0, "contrast": 1.25}, "vig": 0.35}, {"key": "moon", "label": "Moonlight", "recipe": {"saturation": 0.0, "contrast": 1.1, "red": 0.95, "blue": 1.08, "brightness": 0.02}, "vig": 0.2}, {"key": "monofade", "label": "Mono Fade", "recipe": {"saturation": 0.0, "contrast": 0.9, "brightness": 0.02, "fade": 0.18}, "vig": 0}];

  // ---- color matrix 4x5 (skala 0..255), urutan penerapan sama dengan Flutter ----
  var IDENTITY = [1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0];

  // Terapkan `a` lalu `b`.
  function then(a, b) {
    var out = new Array(20).fill(0);
    for (var r = 0; r < 4; r++) {
      for (var c = 0; c < 5; c++) {
        var v = 0;
        for (var k = 0; k < 4; k++) v += b[r * 5 + k] * a[k * 5 + c];
        if (c === 4) v += b[r * 5 + 4];
        out[r * 5 + c] = v;
      }
    }
    return out;
  }

  function saturation(s) {
    var lr = 0.2126, lg = 0.7152, lb = 0.0722, i = 1 - s;
    return [
      lr * i + s, lg * i, lb * i, 0, 0,
      lr * i, lg * i + s, lb * i, 0, 0,
      lr * i, lg * i, lb * i + s, 0, 0,
      0, 0, 0, 1, 0
    ];
  }

  function contrast(c) {
    var t = 128 * (1 - c);
    return [c, 0, 0, 0, t, 0, c, 0, 0, t, 0, 0, c, 0, t, 0, 0, 0, 1, 0];
  }

  function offset(o) {
    return [1, 0, 0, 0, o, 0, 1, 0, 0, o, 0, 0, 1, 0, o, 0, 0, 0, 1, 0];
  }

  function fade(a) {
    var s = 1 - a, o = a * 255 * 0.85;
    return [s, 0, 0, 0, o, 0, s, 0, 0, o, 0, 0, s, 0, o, 0, 0, 0, 1, 0];
  }

  function matrixFor(r) {
    function n(key, d) { return r[key] === undefined ? d : r[key]; }
    var m = IDENTITY;
    m = then(m, saturation(n('saturation', 1)));
    m = then(m, contrast(n('contrast', 1)));
    m = then(m, [
      n('red', 1), 0, 0, 0, n('shiftRed', 0),
      0, n('green', 1), 0, 0, n('shiftGreen', 0),
      0, 0, n('blue', 1), 0, n('shiftBlue', 0),
      0, 0, 0, 1, 0
    ]);
    m = then(m, offset(n('brightness', 0) * 255));
    if (n('fade', 0) > 0) m = then(m, fade(n('fade', 0)));
    return m;
  }

  var BY_KEY = {};
  FILTERS.forEach(function (f) { BY_KEY[f.key] = f; });

  // ---- helpers DOM ----
  function el(tag, cls) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    return e;
  }

  var SVG_NS = 'http://www.w3.org/2000/svg';

  function buildSvgDefs() {
    var svg = document.createElementNS(SVG_NS, 'svg');
    svg.setAttribute('width', '0');
    svg.setAttribute('height', '0');
    svg.setAttribute('aria-hidden', 'true');
    svg.style.position = 'absolute';
    var defs = document.createElementNS(SVG_NS, 'defs');
    svg.appendChild(defs);

    FILTERS.forEach(function (f) {
      if (!f.recipe) return;
      var values = matrixFor(f.recipe).map(function (v, i) {
        // Flutter memakai offset 0..255, SVG memakai 0..1.
        return +((i % 5 === 4) ? v / 255 : v).toFixed(5);
      }).join(' ');
      var filter = document.createElementNS(SVG_NS, 'filter');
      filter.setAttribute('id', 'pbf-' + f.key);
      filter.setAttribute('color-interpolation-filters', 'sRGB');
      var cm = document.createElementNS(SVG_NS, 'feColorMatrix');
      cm.setAttribute('type', 'matrix');
      cm.setAttribute('values', values);
      filter.appendChild(cm);
      defs.appendChild(filter);
    });
    return svg;
  }

  // Satu sel foto: gambar + lapisan vignette (filter diterapkan terpisah).
  function photoCell(url) {
    var cell = el('div', 'pb-cell');
    if (url) {
      var img = el('img');
      img.src = url;
      img.alt = '';
      img.draggable = false;
      cell.appendChild(img);
    } else {
      cell.classList.add('pb-empty');
    }
    cell.appendChild(el('div', 'pb-vig'));
    return cell;
  }

  function applyFilter(root, key) {
    var f = BY_KEY[key] || BY_KEY.none;
    var cssFilter = f && f.recipe ? 'url(#pbf-' + f.key + ')' : 'none';
    var vig = f && f.vig > 0
      ? 'radial-gradient(ellipse farthest-corner at 50% 50%, rgba(0,0,0,0) 55%, rgba(0,0,0,' + f.vig + ') 100%)'
      : 'none';
    root.querySelectorAll('.pb-cell').forEach(function (cell) {
      var img = cell.querySelector('img');
      if (img) img.style.filter = cssFilter;
      cell.querySelector('.pb-vig').style.background = vig;
    });
  }

  // ---- penyusun lembar ----
  function gridOf(cfg, photos, auto) {
    var g = el('div', 'pb-grid' + (auto ? ' pb-auto' : ''));
    g.style.gridTemplateColumns = 'repeat(' + cfg.cols + ', 1fr)';
    g.style.gridTemplateRows = auto ? 'none' : 'repeat(' + cfg.rows + ', 1fr)';
    (cfg.cells || []).forEach(function (pos) {
      g.appendChild(photoCell(pos >= 0 ? photos[pos] : null));
    });
    return g;
  }

  // Lembar dengan gambar background; foto hanya di dalam kotak area.
  function backgroundSheet(cfg, photos, bg) {
    var aspect = bg.aspect > 0 ? bg.aspect : 2 / 3;
    var sheet = el('div', 'pb-sheet');
    sheet.style.aspectRatio = String(aspect);
    var img = el('img', 'pb-bg');
    img.src = bg.url;
    img.alt = '';
    sheet.appendChild(img);
    var area = el('div', 'pb-area');
    area.style.left = bg.area.x + '%';
    area.style.top = bg.area.y + '%';
    area.style.width = bg.area.w + '%';
    area.style.height = bg.area.h + '%';
    area.appendChild(gridOf(cfg, photos, false));
    sheet.appendChild(area);
    return { el: sheet, aspect: aspect };
  }

  // Template dari server: slot diposisikan dengan persen, PNG frame di atas foto.
  function templateSheet(cfg, photos, tpl) {
    var aspect = tpl.canvas_w > 0 && tpl.canvas_h > 0 ? tpl.canvas_w / tpl.canvas_h : 1 / 3;
    var sheet = el('div', 'pb-sheet pb-plain');
    sheet.style.aspectRatio = String(aspect);
    (tpl.slots || []).forEach(function (s, i) {
      var holder = el('div', 'pb-slot');
      holder.style.left = s.x + '%';
      holder.style.top = s.y + '%';
      holder.style.width = s.w + '%';
      holder.style.height = s.h + '%';
      var pos = (cfg.cells || [])[i];
      holder.appendChild(photoCell(pos !== undefined && pos >= 0 ? photos[pos] : null));
      sheet.appendChild(holder);
    });
    if (tpl.image_url) {
      var over = el('img', 'pb-overlay');
      over.src = tpl.image_url;
      over.alt = '';
      sheet.appendChild(over);
    }
    return { el: sheet, aspect: aspect };
  }

  // Frame bawaan tanpa background: lembar putih berbingkai warna frame.
  function plainSheet(cfg, photos) {
    var sheet = el('div', 'pb-sheet pb-plain pb-framed');
    if (cfg.border_color) sheet.style.borderColor = cfg.border_color;
    sheet.appendChild(gridOf(cfg, photos, true));
    var aspect = Math.max(0.4, Math.min(2.6, (cfg.cols * 1.15) / Math.max(1, cfg.rows)));
    return { el: sheet, aspect: aspect };
  }

  function renderSheets(stage, payload) {
    var cfg = payload.composition;
    var photos = payload.photos;
    var make;
    if (payload.template) {
      make = function () { return templateSheet(cfg, photos, payload.template); };
    } else if (payload.background) {
      make = function () { return backgroundSheet(cfg, photos, payload.background); };
    } else {
      make = function () { return plainSheet(cfg, photos); };
    }

    var copies = cfg.copies > 1 ? cfg.copies : 1;
    var column = cfg.copies_direction === 'column';
    var first = null;

    stage.innerHTML = '';
    stage.classList.toggle('pb-column', column);
    for (var i = 0; i < copies; i++) {
      var s = make();
      if (!first) first = s;
      stage.appendChild(s.el);
    }

    // Lebar lembar mengikuti rasionya supaya tidak terlalu besar / kecil.
    var sheetW = Math.max(240, Math.min(620, first.aspect * 460));
    stage.style.maxWidth = (column ? sheetW : sheetW * copies + 16 * (copies - 1)) + 'px';
  }

  // ---- pemilih filter ----
  function buildFilterBar(bar, photos, cfg, onPick) {
    var sample = null;
    (cfg.cells || []).some(function (pos) {
      if (pos >= 0 && photos[pos]) { sample = photos[pos]; return true; }
      return false;
    });
    if (!sample) sample = photos[0] || null;

    FILTERS.forEach(function (f) {
      var item = el('button', 'pb-fitem');
      item.type = 'button';
      item.dataset.key = f.key;
      var thumb = photoCell(sample);
      thumb.classList.add('pb-fthumb');
      item.appendChild(thumb);
      var label = el('span', 'pb-flabel');
      label.textContent = f.label;
      item.appendChild(label);
      item.addEventListener('click', function () { onPick(f.key); });
      bar.appendChild(item);
      applyFilter(thumb, f.key);
    });
  }

  function init(root, payload, opts) {
    opts = opts || {};
    var stage = root.querySelector('[data-pb-stage]');
    var bar = root.querySelector('[data-pb-filters]');
    var saveBtn = root.querySelector('[data-pb-save]');
    var resetBtn = root.querySelector('[data-pb-reset]');
    var status = root.querySelector('[data-pb-status]');
    var nameEl = root.querySelector('[data-pb-filter-name]');

    document.body.appendChild(buildSvgDefs());

    var cfg = payload.composition;
    var saved = payload.filter || 'none';
    var current = saved;

    renderSheets(stage, payload);
    buildFilterBar(bar, payload.photos, cfg, pick);
    pick(current, true);

    function setStatus(text, kind) {
      if (!status) return;
      status.textContent = text || '';
      status.className = 'small ms-2 ' + (kind === 'error' ? 'text-danger' : 'text-success');
    }

    function pick(key, silent) {
      current = BY_KEY[key] ? key : 'none';
      applyFilter(stage, current);
      bar.querySelectorAll('.pb-fitem').forEach(function (b) {
        b.classList.toggle('active', b.dataset.key === current);
      });
      if (nameEl) nameEl.textContent = BY_KEY[current].label;
      var dirty = current !== saved;
      if (saveBtn) saveBtn.disabled = !dirty;
      if (resetBtn) resetBtn.disabled = !dirty;
      if (!silent) setStatus('');
    }

    if (resetBtn) resetBtn.addEventListener('click', function () { pick(saved); });

    if (saveBtn) {
      saveBtn.addEventListener('click', function () {
        saveBtn.disabled = true;
        setStatus('Menyimpan...');
        fetch(payload.save_url, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': opts.csrf || ''
          },
          body: JSON.stringify({ filter: current === 'none' ? null : current })
        }).then(function (res) {
          if (!res.ok) throw new Error('HTTP ' + res.status);
          saved = current;
          pick(current, true);
          setStatus('Filter tersimpan.');
        }).catch(function (e) {
          saveBtn.disabled = false;
          setStatus('Gagal menyimpan: ' + e.message, 'error');
        });
      });
    }
  }

  global.SessionComposition = { init: init, matrixFor: matrixFor, FILTERS: FILTERS };
})(window);
