<div class="row">
    <div class="col-xs-12">
		<div class="box box-primary">
			<div class="box-header">
				<h3 class="box-title"><?= __('staff_log'); ?></h3>

				<div class="box-tools pull-right">
                    <?php
                        if(isset($permissions[$model]['edit']) && $permissions[$model]['edit']){
                            echo $this->Html->link('<i class="glyphicon glyphicon-pencil"></i> '. __d('cinema', 'edit_item'), array('action' => 'edit', $dbdata[$model]['id']), array('class' => 'btn btn-primary', 'escape' => false));
                        } 
                    ?>
	            </div>
			</div>
			<div class="box-body table-responsive">
                <div role="tabpanel">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" role="tablist">
                        <li role=<?= $model ?> class="active">
                            <a href="#info-tab" aria-controls="tab" role="tab" data-toggle="tab">
                                <?= __('staff_log') ?>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="info-tab">
                        <?php if($dbdata[$model]){ ?>
                            <table id="staff_log" class="table table-bordered table-striped">
                                <tbody>
                                    <tr>
                                        <td><strong><?= __('id'); ?></strong></td>
                                        <td>
                                            <?= h($dbdata[$model]['id']); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong><?= __('name'); ?></strong></td>
                                        <td>
                                            <?= h($dbdata[$staff_model]['name']); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong><?= __('clock_in'); ?></strong></td>
                                        <td>
                                            <?= h($dbdata[$model]['clock_in']); ?>
                                        </td>
                                    </tr>                                    
                                    <tr>
                                        <td><strong><?= __('clock_out'); ?></strong></td>
                                        <td>
                                            <?= h($dbdata[$model]['clock_out']); ?>
                                        </td>
                                    </tr>                                    
                                    <tr>
                                        <td><strong><?= __('updated'); ?></strong></td>
                                        <td>
                                            <?= h($dbdata[$model]['updated']); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong><?= __('updated_by'); ?></strong></td>
                                        <td>
                                            <?= h($dbdata['UpdatedBy']['email']); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong><?= __('created'); ?></strong></td>
                                        <td>
                                            <?= h($dbdata[$model]['created']); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong><?= __('created_by'); ?></strong></td>
                                        <td>
                                            <?= h($dbdata['CreatedBy']['email']); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        <?php } ?>
                    </div> <!-- close tabpanel -->
                </div> <!-- close tab-content -->
			</div>
		</div>
	</div>
</div>
	