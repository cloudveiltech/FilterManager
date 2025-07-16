<div class="modal" id="editActivationModal"
     tabindex="-1" role="dialog"
     aria-labelledby="editActivationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content with-tabs">
            <div class="modal-header cv4w">
                <h4 class="modal-title" id="editActivationModalLabel">
                    Edit Activation
                </h4>
            </div>
            <form class="form" v-on:submit.prevent="activationEditor.save()">

                <div class="modal-body">
                    <ul class="nav nav-tabs cv4w theme-alt" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#mainEditor" aria-controls="mainEditor" role="tab" data-toggle="tab">Edit</a>
                        </li>
                        <li role="presentation">
                            <a href="#activationBlacklist" aria-controls="activationBlacklist" role="tab" data-toggle="tab">Blocked Sites</a>
                        </li>
                        <li role="presentation" v-if="isBusinessOwner">
                            <a href="#activationWhitelist" aria-controls="activationWhitelist" role="tab" data-toggle="tab">Allowed Sites</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane active" id="mainEditor">
                            <div class="form-group">
                                <label for="activation_device_id">Computer Name</label>
                                <input id="activation_device_id" class="form-control" type="text" v-model="activationEditor.data.device_id"
                                        readonly="readonly" />
                            </div>

                            <div class="form-group">
                                <label for="activation_identifier">Identifier</label>
                                <input id="activation_identifier" class="form-control" type="text" v-model="activationEditor.data.identifier"
                                        readonly="readonly" />
                            </div>

                            <div class="form-group">
                                <label for="activation_ip_address">IP Address</label>
                                <input id="activation_ip_address" class="form-control" type="text" v-model="activationEditor.data.ip_address"
                                        readonly="readonly" />
                            </div>

                            <div class="form-group">
                                <label for="activation_ip_address">Friendly Name</label>
                                <input id="activation_ip_address" class="form-control" type="text" v-model="activationEditor.data.friendly_name" />
                            </div>

                            <div class="form-group" v-if="isBusinessOwner">
                                <label for="activation_bypass_quantity">Relaxed Policy Bypasses Allowed</label>
                                <input id="activation_bypass_quantity" class="form-control" type="number" v-model="activationEditor.data.bypass_quantity" />
                            </div>

                            <div class="form-group" v-if="isBusinessOwner">
                                <label for="activation_bypass_duration">Relaxed Policy Period (minutes)</label>
                                <input id="activation_bypass_duration" class="form-control" type="number" v-model="activationEditor.data.bypass_period" />
                            </div>

                            <div class="form-group">
                                <label for="activation_bypass_used">Relaxed Policy Bypasses Used</label>
                                <input id="activation_bypass_used" class="form-control" type="number" v-model="activationEditor.data.bypass_used"
                                        readonly="readonly" />
                            </div>

                            <div class="form-group" v-if="isBusinessOwner">
                                <label for="activation_check_in_days">Time until Offline Report (in days)</label>
                                <input id="activation_check_in_days" class="form-control" type="number" min="0" v-model="activationEditor.data.check_in_days" />
                            </div>
                        </div>
                        <div role="tabpanel" class="tab-pane" id="activationBlacklist">
                            <self-moderation-list
                                v-model="activationEditor.blacklist"
                                :activation-edit="true"
                                add-button-text="Block Site"></self-moderation-list>
                        </div>
                        <div role="tabpanel" class="tab-pane" id="activationWhitelist">
                            <self-moderation-list
                                v-model="activationEditor.whitelist"
                                :activation-edit="true"
                                add-button-text="Allow Site"></self-moderation-list>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Submit</button>

                    <button type="button" class="btn btn-secondary" @click="activationEditor.close()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
