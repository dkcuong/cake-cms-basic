<?= $this->Html->css('datatables/dataTables.bootstrap', array('inline' => false)); ?>

<?= $this->element('Content.mobile_menu_filter', array(
    'data_search' => $data_search
)); ?>

<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header">
                <div class="box-tools pull-right">
                    <?php if(isset($permissions[$model]['add']) && ($permissions[$model]['add'] == true)){ ?>
                        <?= $this->Html->link( '<i class="glyphicon glyphicon-plus"></i> ' . __d('promotion_ad', 'add_item'), array('action' => 'add'), array('class' => 'btn btn-primary', 'escape' => false)); ?>
                    <?php } ?>
                </div>
            </div>

            <div class="box-body table-responsive">
                <table id="TicketTypes" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th class="text-center"><?= $this->Paginator->sort('id', __('id')); ?></th>
                        <th class="text-center"><?= $this->Paginator->sort('link', __d('promotion_ad', 'link')); ?></th>
                        <th class="text-center"><?= $this->Paginator->sort('description', __( 'description')); ?></th>
                        <th class="text-center"><?= $this->Paginator->sort('display', __d( 'promotion_ad','display')); ?></th>
                        <th class="text-center"><?= $this->Paginator->sort('enabled',__('enabled')); ?></th>
                        <th class="text-center"><?= $this->Paginator->sort('updated',__('updated')); ?></th>
                        <th class="text-center"><?= __('operation'); ?></th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php
                    // pr($dbdatas);
                    // exit;
                    ?>
                    <?php foreach ($dbdatas as $dbdata): ?>
                        <tr>
                            <td class="text-center"><?= h($dbdata[$model]['id']); ?>&nbsp;</td>
                            <td class="text-center">
                                <?= h($dbdata[$model]['link']); ?>
                            </td>

                            <td class="text-center">
                                <?= h($dbdata[$model]['description']); ?>
                            </td>

                            <td class="text-center">
                                <?= __(h($dbdata[$model]['display'])); ?>
                            </td>

                            <td class="text-center">
                                <?= $this->element('view_check_ico', array('_check' => $dbdata[$model]['enabled'])); ?>
                            </td>

                            <td class="text-center"><?= h($dbdata[$model]['updated']); ?>&nbsp;</td>
                            <td class="text-center">
                                <?= $this->Html->link('<i class="glyphicon glyphicon-eye-open"></i>', array('action' => 'view', $dbdata[$model]['id']), array('class' => 'btn btn-primary btn-xs', 'escape' => false, 'data-toggle'=>'tooltip', 'title' => __('view'))); ?>
                                <?php if(isset($permissions[$model]['edit']) && ($permissions[$model]['edit'] == true)){ ?>
                                    <?= $this->Html->link('<i class="glyphicon glyphicon-pencil"></i>', array('action' => 'edit', $dbdata[$model]['id']), array('class' => 'btn btn-warning btn-xs', 'escape' => false, 'data-toggle'=>'tooltip', 'title' => __('edit'))); ?>
                                <?php } ?>
                                <?php if(isset($permissions[$model]['delete']) && ($permissions[$model]['delete'] == true)){ ?>
                                    <?= $this->Form->postLink('<i class="glyphicon glyphicon-trash"></i>', array('action' => 'delete', $dbdata[$model]['id']), array('class' => 'btn btn-danger btn-xs', 'escape' => false, 'data-toggle'=>'tooltip', 'title' => __('delete')), __('are_you_sure_to_delete', $dbdata[$model]['id'])); ?>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?= $this->element('Paginator'); ?>
    </div>
</div>

<?php
echo $this->Html->script('plugins/datatables/jquery.dataTables', array('inline' => false));
echo $this->Html->script('plugins/datatables/dataTables.bootstrap', array('inline' => false));
?>
<script type="text/javascript">
    $(document).ready(function(){

    });
</script>