/*global globalAjaxBellcomDataIOServiceToken:true*/
$(document).ready(function(){
  $("body").delegate("form.configs", "submit", function(event) {
    event.preventDefault();
    var $status = $(this).parent().parent().find('td.status');
    $status.show();
    $(this).find('button').attr('disabled','disabled');
    $.ajax({
      url: '/modules/bellcomDataIOService/ajax.php?method=startImport&ajax=true&token='+globalAjaxBellcomDataIOServiceToken,
      type: 'post',
      dataType: 'json',
      data: $(this).serialize(),
      success: function(data) {
        $status.html('Import afsluttet');
      }
    });
  });

  var $configListContainer = $("#config-list-container");
  $.ajax({
    url: '/modules/bellcomDataIOService/ajax.php?method=getConfigList&ajax=true&token='+globalAjaxBellcomDataIOServiceToken,
    type: 'post',
    dataType: 'json',
    success: function(data) {
      $.each(data,function(index, item) {
        $configListContainer.append( '<tr><td'+ (item.disabled ? ' class="disabled"' : '') +'><h2>'+item.name+'</h2>'+item.desc+'</td><td><form class="configs" action="#"><input type="hidden" name="config-file" value="'+item.file+'"/><button'+ (item.disabled ? ' disabled="disabled"' : '') +' type="submit">Start import</button></form></td><td class="status" style="display: none"><img src="/img/loadingAnimation.gif" alt="loading"/></td></tr>' );
      });
    }
  });
});
