<?php

namespace WHMCS\Module\Addon\DisableRegistrarLock\Admin;

use WHMCS\Database\Capsule;

class Controller {
    
    /**
     * Index action.
     *
     * @param array $vars Module configuration parameters
     *
     * @return string
     */
    public function index( $vars )
    {
        $modulelink = $vars['modulelink'];

        $LANG = $vars['_lang'];

        $getEnabled = Capsule::table( 'mod_disable_registrar_lock' )->first();
        $enabled = json_decode( $getEnabled->enabled );

        $getTLDs = Capsule::table( 'tbldomainpricing' )->get();

        $checkboxes = '';

        foreach( $getTLDs as $tld ) {
            $extension = ltrim( $tld->extension, "." );

            $checked = ( in_array( $extension, $enabled ) ) ? 'checked="checked"' : '';

            $checkboxes .= '<div class="checkbox">
                <label>
                    <input type="checkbox" id="' . $extension . '" class="tld" name="tlds[' . $extension . ']" ' . $checked . '> .' . $extension . '
                </label>
            </div>';
        }

        $alert = '';
        if ( isset( $_SESSION['alert'] ) ) {
            $alert .= '<div class="alert alert-' . $_SESSION['alert']['status'] . '">
                <i class="fa fa-lg fa-check-circle"></i> ' . $_SESSION['alert']['description'] . '
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>';

            unset( $_SESSION['alert'] );
        }

        return <<<EOF
{$alert}
<form action="{$modulelink}" method="post" class="form-horizontal">
    <input type="hidden" name="action" value="savesetting" />
    <div class="form-group">
        <label class="col-sm-2 control-label">{$LANG['label_name']}</label>
        <div class="col-sm-3">
            {$checkboxes}
            <div>
                <input type="button" id="check-all" class="btn btn-link" value="{$LANG['check_all']}">
                <input type="button" id="uncheck-all" class="btn btn-link" value="{$LANG['uncheck_all']}">
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-3">
            <button type="submit" class="btn btn-block btn-success">{$LANG['submit_button']}</button>
        </div>
    </div>
</form>

<script type="text/javascript">
    (function(){
        function checkAll() {
            var inputs = document.querySelectorAll('.tld');
            for(var i = 0; i < inputs.length; i++) {
                inputs[i].checked = true;
            }
        }
    
        function uncheckAll() {
            var inputs = document.querySelectorAll('.tld');
            for(var i = 0; i < inputs.length; i++) {
                inputs[i].checked = false;
            }
        }

        document.getElementById('check-all').addEventListener('click', checkAll);
        document.getElementById('uncheck-all').addEventListener('click', uncheckAll);
    })();
</script>
EOF;
    }

    /**
     * Save action.
     *
     * @param array $vars Module configuration parameters
     *
     * @return string
     */
    public function savesetting( $vars )
    {
        $modulelink = $vars['modulelink'];

        $LANG = $vars['_lang'];

        $tlds = [];

        foreach ( $_REQUEST['tlds'] as $tld => $status ) {
            if ( 'on' == $status ) {
                $tlds[] = $tld;
            }
        }

        try {
            $get = Capsule::table( 'mod_disable_registrar_lock' )->first();

            $action = Capsule::table( 'mod_disable_registrar_lock' )
                ->where( 'id', $get->id )
                ->update([
                    'enabled' => json_encode($tlds),
                ]);
        
            $_SESSION['alert'] = [
                'status' => 'success',
                'description' => $LANG['config_saved']
            ];
        } catch ( \Exception $e ) {
            $_SESSION['alert'] = [
                'status' => 'error',
                'description' => $LANG['config_not_saved'] . ' (' . $e->getMessage() . ')'
            ];
        }

        header( 'Location: ' . $modulelink );
        exit;
    }

}
