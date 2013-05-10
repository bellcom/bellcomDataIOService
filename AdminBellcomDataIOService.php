<?php

class AdminBellcomDataIOService extends AdminTab
{
  /**
   * display
   * @return void
   * @author Henrik Farre <hf@bellcom.dk>
   **/
  public function display()
  {
    echo '<script type="text/javascript">var globalAjaxBellcomDataIOServiceToken = "'.sha1(_COOKIE_KEY_.'ajaxBellcomDataIOService').'";</script>
      <script type="text/javascript" src="/modules/bellcomDataIOService/import.js"></script>
      <h1>Bellcom Import</h1>
<em>Start ikke en import flere gange.</em><br/><br/>
Når en import køre kan du forlade side, importen kører videre i baggrunden, du vil dog ikke kunne se hvornår den stopper.<br/><br/>
      <table id="config-list-container"></table>';
  }
}
