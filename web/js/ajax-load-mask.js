$(document).ajaxStart(function () {
    console.log(['ajaxStart']);
    $('body').mask('Загрузка...');
});
$(document).ajaxStop(function () {
    console.log(['ajaxStop']);
    $('body').unmask();
});