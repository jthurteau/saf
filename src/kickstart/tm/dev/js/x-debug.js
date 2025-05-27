
$(function() {
    let collapseNodes = $('.trace .param-value .entry-type, .canister .entry-type');
    let collapsedNodes = collapseNodes.next().children('* > .serial');
    collapsedNodes.hide();
    let activeNodes = collapsedNodes.parent().parent();
    activeNodes.addClass('saf-collapsed');
    activeNodes.on('click', function(e) {
        $(e.target).next().children('* > .serial').toggle();
        $(this).toggleClass('saf-collapsed saf-expanded');
        e.stopImmediatePropagation();
    })
});