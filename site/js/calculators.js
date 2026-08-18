(function () {
  var form = document.getElementById("calc-form");
  if (!form) return;
  var out = document.getElementById("calc-result");
  var type = form.getAttribute("data-calc");

  function n(name) {
    return parseFloat(form[name].value) || 0;
  }

  function money(v) {
    return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(v);
  }

  function run() {
    if (type === "tax") {
      var tax = n("amount") * (n("rate") / 100);
      out.textContent = "Tax " + money(tax) + " · Total " + money(n("amount") + tax);
    } else if (type === "margin") {
      var profit = n("price") - n("cost");
      var margin = n("price") ? (profit / n("price")) * 100 : 0;
      out.textContent = "Profit " + money(profit) + " · Margin " + margin.toFixed(1) + "%";
    } else if (type === "discount") {
      var off = n("amount") * (n("rate") / 100);
      out.textContent = "Discount " + money(off) + " · Pay " + money(n("amount") - off);
    } else if (type === "late") {
      var fee = n("amount") * (n("rate") / 100) * n("months");
      out.textContent = "Late fee " + money(fee) + " · Amount due " + money(n("amount") + fee);
    }
  }

  form.addEventListener("input", run);
  run();
})();
