yii.confirm = function (message, ok, cancel) {
    bootbox.confirm(message, function (confirmed) {
        if (confirmed) {
          !ok || ok();
        }else{
          !cancel || cancel();
        }
    });
}


