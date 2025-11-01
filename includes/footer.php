<!-- includes/footer.php -->
<footer class="footer-light">
  <hr>
  <p>&copy; 2025 XSports India Pvt Ltd. All rights reserved.</p>
</footer>

<!-- Size selection modal for Buy Now (injected site-wide) -->
<div id="sizeModalOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
  <div id="sizeModal" style="background:#fff;max-width:420px;width:95%;padding:18px;border-radius:8px;box-shadow:0 6px 24px rgba(0,0,0,0.25);">
    <h3 style="margin-top:0;margin-bottom:8px;">Select a size</h3>
    <div id="sizeList" style="margin-bottom:12px;">Loading sizes…</div>
    <div style="display:flex;gap:8px;justify-content:flex-end;">
      <button id="sizeCancel" type="button" style="background:#eee;border:1px solid #ccc;padding:8px 12px;border-radius:6px;">Cancel</button>
      <button id="sizeConfirm" type="button" style="background:#005eb8;color:#fff;border:1px solid #005eb8;padding:8px 12px;border-radius:6px;">Buy Now</button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Intercept any form that posts to buy_now.php or add_to_cart.php
  document.querySelectorAll('form').forEach(function(form) {
    try {
      var action = (form.getAttribute('action') || '').toLowerCase();
      // Skip interception on full product pages which have their own size UI (they use class "size-aware-form")
      var isProductPageForm = form.classList.contains('size-aware-form') || form.closest('.product-detail');
      if (isProductPageForm) {
        return;
      }

      if (action.indexOf('buy_now.php') !== -1 || action.indexOf('add_to_cart.php') !== -1) {
        form.addEventListener('submit', function (ev) {
          // prevent immediate submit; decide based on whether product has sizes
          var productIdInput = form.querySelector('input[name="product_id"]');
          if (!productIdInput) return; // nothing to do
          var pid = productIdInput.value;
          ev.preventDefault();

          // fetch sizes for this product
          fetch('get_product_sizes.php?id=' + encodeURIComponent(pid))
            .then(function(res) { return res.json(); })
            .then(function(json) {
              if (!json || !json.sizes || json.sizes.length === 0) {
                // no sizes -> submit form normally
                form.submit();
                return;
              }

              // show modal with size options; pass which action we intercepted
              var intended = action.indexOf('add_to_cart.php') !== -1 ? 'add_to_cart' : 'buy_now';
              showSizeModal(pid, form, json.sizes, intended);
            })
            .catch(function() {
              // on error, fallback to submitting the form
              form.submit();
            });
        });
      }
    } catch (e) {
      // ignore
    }
  });

  var overlay = document.getElementById('sizeModalOverlay');
  var sizeList = document.getElementById('sizeList');
  var sizeConfirm = document.getElementById('sizeConfirm');
  var sizeCancel = document.getElementById('sizeCancel');
  var activeForm = null;
  var activeProductId = null;

  function showSizeModal(pid, form, sizes, intendedAction) {
    activeForm = form;
    activeProductId = pid;
    // set confirm button label based on action
    if (intendedAction === 'add_to_cart') {
      sizeConfirm.textContent = 'Add to Cart';
    } else {
      sizeConfirm.textContent = 'Buy Now';
    }
    // build radio list
    if (!sizes || sizes.length === 0) {
      sizeList.innerHTML = '<p>No sizes available</p>';
    } else {
      var html = '<form id="__size_selector_form">';
      sizes.forEach(function(s, idx) {
        var disabled = (typeof s.stock !== 'undefined' && parseInt(s.stock) <= 0) ? 'disabled' : '';
        html += '<div style="margin:6px 0;"><label style="display:flex;align-items:center;gap:8px;">'
             + '<input type="radio" name="selected_size" value="' + escapeHtml(s.size) + '" ' + (idx===0 && !disabled ? 'checked' : '') + ' ' + disabled + '> '
             + '<span>' + escapeHtml(s.size) + (disabled ? ' (out of stock)' : '') + '</span>'
             + '</label></div>';
      });
      html += '</form>';
      sizeList.innerHTML = html;
    }

    overlay.style.display = 'flex';
  }

  function hideSizeModal() {
    overlay.style.display = 'none';
    sizeList.innerHTML = '';
    activeForm = null;
    activeProductId = null;
  }

  sizeCancel.addEventListener('click', function () {
    hideSizeModal();
  });

  sizeConfirm.addEventListener('click', function () {
    if (!activeForm || !activeProductId) {
      hideSizeModal();
      return;
    }
    var selectorForm = document.getElementById('__size_selector_form');
    if (!selectorForm) {
      hideSizeModal();
      return;
    }
    var selected = selectorForm.querySelector('input[name="selected_size"]:checked');
    if (!selected) {
      alert('Please select a size');
      return;
    }

  // create a temp form and submit to buy_now.php or add_to_cart.php with selected_size
  var temp = document.createElement('form');
  temp.method = 'post';
  // if the active form was add_to_cart, submit to add_to_cart.php, otherwise buy_now.php
  var originalAction = (activeForm.getAttribute('action') || '').toLowerCase();
  temp.action = originalAction.indexOf('add_to_cart.php') !== -1 ? 'add_to_cart.php' : 'buy_now.php';
    var pidInput = document.createElement('input');
    pidInput.type = 'hidden';
    pidInput.name = 'product_id';
    pidInput.value = activeProductId;
    var sizeInput = document.createElement('input');
    sizeInput.type = 'hidden';
    sizeInput.name = 'selected_size';
    sizeInput.value = selected.value;
    temp.appendChild(pidInput);
    temp.appendChild(sizeInput);
    document.body.appendChild(temp);
    temp.submit();
  });

  // simple html escape
  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"'`]/g, function (s) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;', '`':'&#x60;'}[s];
    });
  }

});
</script>

</body>
</html>
