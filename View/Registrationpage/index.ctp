<div class="div-registrationpage">
        
    <div class="dialog-create-member">
        <div class="div-dialog-member">
            <div class="div-input-phone">
                <input type="text" class="edit-country-code content smallest light" placeHolder="+852">
                <input type="text" class="edit-phone content smallest light" placeHolder="XXXX7823">
            </div>
            <div class="div-member-notes1 title">Register new member</div>
            <div class="div-member-notes2 content light small">Input new member's phone to register, <br/>and sytem will send sms of registration link</div>
            <div class="div-button-member">
                <button type='button' class='btn-register-stand-alone title small narrow light-brown'>
                    REGISTER
                </button>
            </div>
        </div>
    </div>

</div>

<script type="text/javascript">
    $(document).ready(function() {
        COMMON.token = '<?= $staff['Staff']['token'] ?>';
        COMMON.staff_id = '<?= $staff['Staff']['id'] ?>';

        COMMON.url_do_pos_registration = '<?= Router::url(array('plugin' => 'member','controller' => 'members', 'action' => 'do_pos_registration', 'api' => true), true); ?>';
        COMMON.init_page();
    });
</script>