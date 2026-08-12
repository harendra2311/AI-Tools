(function () {
  var form = document.getElementById("doc-form");
  if (!form) return;

  var type = form.getAttribute("data-doc-type") || "invoice";
  var labels = {
    invoice: "Invoice",
    receipt: "Receipt",
    quote: "Quote",
    estimate: "Estimate",
    proforma: "Proforma Invoice",
    "credit-note": "Credit Note",
    "recurring-invoice": "Recurring Invoice",
    "purchase-order": "Purchase Order"
  };

  var templates = {
    freelancer: { fromName: "Alex Rivera", fromMeta: "Independent consultant\nAustin, TX", item: "Monthly freelance services" },
    developer: { fromName: "Bytecraft Labs", fromMeta: "Web development\nLondon, UK", item: "Feature sprint" },
    designer: { fromName: "Atelier North", fromMeta: "Graphic design studio\nBerlin", item: "Brand identity package" },
    photographer: { fromName: "Lumen Studio", fromMeta: "Photography\nMelbourne", item: "Half-day photoshoot" },
    consultant: { fromName: "Meridian Advisory", fromMeta: "Strategy consulting\nToronto", item: "Workshop facilitation" },
    contractor: { fromName: "Field & Frame Co.", fromMeta: "Contracting\nDenver, CO", item: "Site labor — week 12" },
    agency: { fromName: "Harbor Agency", fromMeta: "Marketing\nNew York, NY", item: "Campaign management" },
    "small-business": { fromName: "Oak & Pine Supply", fromMeta: "Retail & wholesale\nManchester", item: "Wholesale order #441" }
  };

  var itemsBody = document.getElementById("items-body");
  var preview = document.getElementById("preview");
  var addBtn = document.getElementById("add-item");
  var printBtn = document.getElementById("download-pdf");
  var saveBtn = document.getElementById("save-local");

  function rowHtml(item, qty, rate) {
    return (
      '<tr>' +
      '<td><input name="item" value="' + (item || "") + '" placeholder="Description"></td>' +
      '<td><input name="qty" type="number" min="0" step="0.01" value="' + (qty || 1) + '"></td>' +
      '<td><input name="rate" type="number" min="0" step="0.01" value="' + (rate || 0) + '"></td>' +
      '<td><button type="button" class="btn btn-ghost remove">Remove</button></td>' +
      "</tr>"
    );
  }

  function rows() {
    return Array.prototype.map.call(itemsBody.querySelectorAll("tr"), function (tr) {
      return {
        item: tr.querySelector('[name="item"]').value,
        qty: parseFloat(tr.querySelector('[name="qty"]').value) || 0,
        rate: parseFloat(tr.querySelector('[name="rate"]').value) || 0
      };
    });
  }

  function money(n, currency) {
    try {
      return new Intl.NumberFormat("en-US", { style: "currency", currency: currency || "USD" }).format(n || 0);
    } catch (e) {
      return (currency || "USD") + " " + (n || 0).toFixed(2);
    }
  }

  function state() {
    var data = Object.fromEntries(new FormData(form).entries());
    data.items = rows();
    data.tax = parseFloat(data.tax) || 0;
    data.discount = parseFloat(data.discount) || 0;
    var subtotal = data.items.reduce(function (sum, row) {
      return sum + row.qty * row.rate;
    }, 0);
    var discountAmt = subtotal * (data.discount / 100);
    var taxed = (subtotal - discountAmt) * (data.tax / 100);
    data.subtotal = subtotal;
    data.discountAmt = discountAmt;
    data.taxAmt = taxed;
    data.total = subtotal - discountAmt + taxed;
    return data;
  }

  function render() {
    var s = state();
    var title = labels[type] || "Invoice";
    preview.innerHTML =
      '<div class="inv-top">' +
      '<div><strong>' + escapeHtml(s.fromName || "Your business") + '</strong><div class="muted-pre">' + escapeHtml(s.fromMeta || "") + "</div></div>" +
      '<div class="inv-meta"><small>' + title + "</small><b>#" + escapeHtml(s.number || "0001") + "</b>" +
      "<small>" + escapeHtml(s.date || "") + (s.due ? " · Due " + escapeHtml(s.due) : "") + "</small></div></div>" +
      '<div class="inv-grid"><div><div class="mini-label">Bill To</div><strong>' +
      escapeHtml(s.toName || "Client") +
      '</strong><div class="muted-pre">' +
      escapeHtml(s.toMeta || "") +
      "</div></div><div><div class='mini-label'>Payment</div>" +
      escapeHtml(s.payment || "") +
      "</div></div>" +
      '<table class="inv-table"><thead><tr><th>Item</th><th>Qty</th><th class="num">Rate</th><th class="num">Amount</th></tr></thead><tbody>' +
      s.items
        .map(function (row) {
          return (
            "<tr><td>" +
            escapeHtml(row.item || "—") +
            "</td><td>" +
            row.qty +
            '</td><td class="num">' +
            money(row.rate, s.currency) +
            '</td><td class="num">' +
            money(row.qty * row.rate, s.currency) +
            "</td></tr>"
          );
        })
        .join("") +
      "</tbody></table>" +
      '<div class="inv-foot"><div class="pay-box">' +
      escapeHtml(s.notes || "") +
      '</div><div class="totals"><div><span>Subtotal</span><span>' +
      money(s.subtotal, s.currency) +
      "</span></div><div><span>Discount</span><span>" +
      money(s.discountAmt, s.currency) +
      "</span></div><div><span>Tax</span><span>" +
      money(s.taxAmt, s.currency) +
      '</span></div><div class="grand"><span>Total</span><span>' +
      money(s.total, s.currency) +
      "</span></div></div></div>";
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/\n/g, "<br>");
  }

  itemsBody.addEventListener("click", function (e) {
    if (e.target.classList.contains("remove")) {
      e.target.closest("tr").remove();
      if (!itemsBody.querySelector("tr")) itemsBody.insertAdjacentHTML("beforeend", rowHtml("Service", 1, 100));
      render();
    }
  });

  addBtn.addEventListener("click", function () {
    itemsBody.insertAdjacentHTML("beforeend", rowHtml("", 1, 0));
  });

  form.addEventListener("input", render);

  printBtn.addEventListener("click", function () {
    window.print();
  });

  saveBtn.addEventListener("click", function () {
    localStorage.setItem("draftline-" + type, JSON.stringify(state()));
    saveBtn.textContent = "Saved locally";
    setTimeout(function () {
      saveBtn.textContent = "Save locally";
    }, 1600);
  });

  var params = new URLSearchParams(location.search);
  var tpl = templates[params.get("template")];
  if (tpl) {
    form.fromName.value = tpl.fromName;
    form.fromMeta.value = tpl.fromMeta;
    itemsBody.innerHTML = rowHtml(tpl.item, 1, 250);
  } else if (!itemsBody.querySelector("tr")) {
    itemsBody.innerHTML = rowHtml("Professional services", 1, 250);
  }

  var saved = localStorage.getItem("draftline-" + type);
  if (saved && !tpl) {
    try {
      var parsed = JSON.parse(saved);
      Object.keys(parsed).forEach(function (key) {
        if (form[key] && key !== "items") form[key].value = parsed[key];
      });
      if (parsed.items && parsed.items.length) {
        itemsBody.innerHTML = parsed.items
          .map(function (row) {
            return rowHtml(row.item, row.qty, row.rate);
          })
          .join("");
      }
    } catch (e) {}
  }

  var today = new Date().toISOString().slice(0, 10);
  if (!form.date.value) form.date.value = today;
  render();
})();
