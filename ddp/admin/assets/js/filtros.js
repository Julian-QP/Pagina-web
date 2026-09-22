document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-content-filter]').forEach(function (filter) {
    var container = document.querySelector(filter.getAttribute('data-content-filter'));
    if (!container) return;
    var input = filter.querySelector('[data-filter-name]');
    var select = filter.querySelector('[data-filter-status]');
    var cards = container.querySelectorAll('[data-filter-card]');
    var apply = function () {
      var name = (input ? input.value : '').toLowerCase().trim();
      var status = select ? select.value : '';
      cards.forEach(function (card) {
        var matchesName = !name || (card.getAttribute('data-name') || '').toLowerCase().includes(name);
        var matchesStatus = !status || card.getAttribute('data-status') === status;
        card.hidden = !(matchesName && matchesStatus);
      });
    };
    if (input) input.addEventListener('input', apply);
    if (select) select.addEventListener('change', apply);
  });
});
