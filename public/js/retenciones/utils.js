/* ============================================================
 * Retenciones — utilidades compartidas (formateo y parseo).
 * Scope: solo módulo Retenciones (cargar antes de index.js / create.js).
 * Expone window.RetUtils.
 * ============================================================ */

(function (global) {
  'use strict';

  /* ── Constantes del módulo ────────────────────────────────── */
  var DEFAULT_LOCALE  = 'es-PE';   // miles "," y decimales "."
  var DEFAULT_DECIMALS = 2;        // Importes y totales
  var TC_DECIMALS      = 2;        // Tipo de cambio (regla actual del módulo)
  var TC_DEFAULT       = 3.75;     // TC referencial PEN/USD

  /* ── Helpers de formateo ──────────────────────────────────── */

  /** Símbolo de moneda. PEN → "S/", USD → "US$", otros → 'XXX'. */
  function moneySymbol(currency) {
    if (currency === 'USD') return 'US$';
    if (!currency || currency === 'PEN') return 'S/';
    return currency;
  }

  /** Formatea un número con separador de miles + decimales fijos.
   *  fmtNum(1234.5)         -> "1,234.50"
   *  fmtNum(1234.567, 3)    -> "1,234.567"
   *  fmtNum(null)           -> "0.00"
   */
  function fmtNum(n, decimals) {
    if (decimals === undefined || decimals === null) decimals = DEFAULT_DECIMALS;
    var num = Number(n);
    if (!isFinite(num)) num = 0;
    return num.toLocaleString(DEFAULT_LOCALE, {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    });
  }

  /** Formatea con símbolo de moneda. fmtMoney(1234.5, 'USD') -> "US$ 1,234.50" */
  function fmtMoney(n, currency, decimals) {
    return moneySymbol(currency) + ' ' + fmtNum(n, decimals);
  }

  /* ── Parseo robusto ───────────────────────────────────────── */

  /** Convierte un string ingresado por el usuario a Number.
   *  - "" / null / undefined → 0
   *  - "1234.56"   → 1234.56
   *  - "1234,56"   → 1234.56  (coma decimal estilo europeo)
   *  - "1,234.56"  → 1234.56  (coma=miles, punto=decimal)
   *  - "1.234,56"  → 1234.56  (punto=miles, coma=decimal)
   *  - basura      → 0
   */
  function parseNum(raw) {
    if (raw === null || raw === undefined) return 0;
    var s = String(raw).trim();
    if (s === '') return 0;

    var hasComma = s.indexOf(',') !== -1;
    var hasDot   = s.indexOf('.') !== -1;

    if (hasComma && hasDot) {
      // Decide cuál separador es decimal: el más a la derecha
      if (s.lastIndexOf(',') > s.lastIndexOf('.')) {
        // Estilo europeo: 1.234,56
        s = s.replace(/\./g, '').replace(',', '.');
      } else {
        // Estilo es-PE/inglés: 1,234.56
        s = s.replace(/,/g, '');
      }
    } else if (hasComma) {
      // Solo coma: asumirla como decimal
      s = s.replace(',', '.');
    }
    var n = parseFloat(s);
    return isFinite(n) ? n : 0;
  }

  /** Trunca un número a N decimales (sin redondear). */
  function truncDecimals(n, decimals) {
    var factor = Math.pow(10, decimals);
    return Math.trunc(parseNum(n) * factor) / factor;
  }

  /** Redondea un número a N decimales (HALF_UP). */
  function roundDecimals(n, decimals) {
    var factor = Math.pow(10, decimals);
    return Math.round(parseNum(n) * factor) / factor;
  }

  /** Limita el valor de un <input type="number"> a N decimales mientras el
   *  usuario escribe. Llamar desde un handler 'input'. */
  function clampDecimalsOnInput(inputEl, decimals) {
    if (decimals === undefined) decimals = TC_DECIMALS;
    var v = inputEl.value;
    if (!v) return;
    var dot = v.indexOf('.');
    if (dot === -1) return;
    var deci = v.length - dot - 1;
    if (deci > decimals) {
      inputEl.value = v.substring(0, dot + 1 + decimals);
    }
  }

  /* ── Exponer ──────────────────────────────────────────────── */
  global.RetUtils = {
    // formateo
    moneySymbol: moneySymbol,
    fmtNum:      fmtNum,
    fmtMoney:    fmtMoney,
    // parseo
    parseNum:    parseNum,
    truncDecimals:  truncDecimals,
    roundDecimals:  roundDecimals,
    // dom helpers
    clampDecimalsOnInput: clampDecimalsOnInput,
    // constantes
    LOCALE:        DEFAULT_LOCALE,
    DECIMALS:      DEFAULT_DECIMALS,
    TC_DECIMALS:   TC_DECIMALS,
    TC_DEFAULT:    TC_DEFAULT,
  };
})(window);
