/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

///<reference path="../../progresswait.ts"/>

namespace Citadel {

    interface ActionCompleteCallback {
        (action: string): void;
    }

    export class TimeRestrictionUI {
        /**
         * TIME RESTRICTIONS
         */
        public timeRestrictionsSliderConfig: any;
        public timeRestrictionConfigs: any;
        public timeRestrictionSliders: any;
        public timeRestrictions: any;
        private elemId: string

        public savedCustom: any;
        public timeRestrictionsPresets: any = {
            evening: {
                monday: {RestrictionsEnabled: true, EnabledThrough: [5, 20]},
                tuesday: {RestrictionsEnabled: true, EnabledThrough: [5, 20]},
                wednesday: {RestrictionsEnabled: true, EnabledThrough: [5, 20]},
                thursday: {RestrictionsEnabled: true, EnabledThrough: [5, 20]},
                friday: {RestrictionsEnabled: true, EnabledThrough: [5, 20]},
                saturday: {RestrictionsEnabled: true, EnabledThrough: [5, 20]},
                sunday: {RestrictionsEnabled: true, EnabledThrough: [5, 20]}
            },

            office: {
                monday: {RestrictionsEnabled: true, EnabledThrough: [7, 18]},
                tuesday: {RestrictionsEnabled: true, EnabledThrough: [7, 18]},
                wednesday: {RestrictionsEnabled: true, EnabledThrough: [7, 18]},
                thursday: {RestrictionsEnabled: true, EnabledThrough: [7, 18]},
                friday: {RestrictionsEnabled: true, EnabledThrough: [7, 18]},
                saturday: {RestrictionsEnabled: true, EnabledThrough: [10, 15]},
                sunday: {RestrictionsEnabled: true, EnabledThrough: [0, 0]}
            }
        };

        private m_bindings: BindingInstance;
        public constructor(bindings: BindingInstance, elementId: string) {
            this.elemId = elementId;
            this.m_bindings = bindings;
            this.timeRestrictionsSliderConfig = {
                start: [0, 24],
                connect: true,
                range: {
                    'min': 0,
                    'max': 24
                },

                step: 0.25
            };
        }

        // Someday these will need to be loaded from a database, but for now, we'll just hard code them.
        public EveningRestrictionsPreset(): void {
            this.loadTimeRestrictionsFrom(this.timeRestrictionsPresets.evening);
        }

        public OfficeRestrictionsPreset(): void {
            this.loadTimeRestrictionsFrom(this.timeRestrictionsPresets.office);
        }

        public HasRestrictions(): boolean {
            for (var day of this.WEEKDAYS) {
                if(this.timeRestrictions[day].EnabledThrough[0] != 0 ||
                    this.timeRestrictions[day].EnabledThrough[1] != 24 ||
                    this.timeRestrictions[day].RestrictionsEnabled) {
                    return true;
                }
            }
            return false;
        }

        public NoneRestrictionsPreset(): void {
            this.InitEmptyTimeRestrictionsObject();
            this.m_bindings.Refresh();
            this.updateTimeRestrictionsSliders();
        }

        private loadTimeRestrictionsFrom(obj): void {
            this.savedCustom = JSON.parse(JSON.stringify(this.timeRestrictions));

            for (var i in this.timeRestrictions) {
                this.timeRestrictions[i] = JSON.parse(JSON.stringify(obj[i]));
            }

            this.m_bindings.Refresh();
            this.updateTimeRestrictionsSliders();
        }

        WEEKDAYS = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];
        public updateTimeRestrictionsSliders(): void {
            var days = this.WEEKDAYS;

            var sliders = this.timeRestrictionSliders;
            var restrictionsElem = $(this.elemId + " .time_restrictions");

            for (var day of days) {
                let slider: any = restrictionsElem.find("." + day).get(0);

                if (slider && slider.noUiSlider) {
                    let restrictionData: any = this.timeRestrictions[day].EnabledThrough;
                    slider.noUiSlider.set(restrictionData);
                }
            }
        }

        public InitEmptyTimeRestrictionsObject(): void {
            this.timeRestrictions = {};

            for (var day of this.WEEKDAYS) {
                this.timeRestrictions[day] = {
                    EnabledThrough: [0, 24],
                    RestrictionsEnabled: false
                };
            }
        }


        public InitTimeRestrictions(): void {
            var days = this.WEEKDAYS;

            var restrictionsElem = $(this.elemId + " .time_restrictions");

            var configs = {};
            var sliders = {};

            function generateLabelFn(that, day, slider) {
                return function () {
                    that.timeRestrictions[day].internetLabel = that.generateInternetLabel(that.timeRestrictions[day], slider);
                    that.m_bindings.Refresh();
                };
            }

            for (var day of days) {
                configs[day] = JSON.parse(JSON.stringify(this.timeRestrictionsSliderConfig));

                if (this.timeRestrictions && day in this.timeRestrictions && this.timeRestrictions[day].EnabledThrough) {
                    configs[day].start = this.timeRestrictions[day].EnabledThrough;
                }

                let slider: any = restrictionsElem.find("." + day).get(0);

                if (slider && slider.noUiSlider) {
                    slider.noUiSlider.destroy();
                }

                noUiSlider.create(slider, configs[day]);
                slider.noUiSlider.on('set', this.generateInternetLabelCallback(day, slider));

                sliders[day] = slider;

                this.timeRestrictions[day].internetLabel = this.generateInternetLabel(this.timeRestrictions[day], slider);
                this.timeRestrictions[day].generateInternetLabel = generateLabelFn(this, day, slider);
            }

            this.timeRestrictionConfigs = configs;
            this.timeRestrictionSliders = sliders;

            this.m_bindings.Refresh();
        }

        private generateInternetLabelCallback(day, sliderElem): any {
            var that = this;
            return function (values, handle, unencoded, tap, positions) {
                console.log(unencoded);
                that.timeRestrictions[day].EnabledThrough = unencoded;
                that.timeRestrictions[day].internetLabel = that.generateInternetLabel(that.timeRestrictions[day], sliderElem);
            }
        }

        private generateInternetLabel(entry, sliderElem) {
            if (!entry) {
                entry = {
                    EnabledThrough: [0, 24],
                    RestrictionsEnabled: false
                }
            }

            var enabledTimes = (entry && entry.EnabledThrough) ? entry.EnabledThrough : [0, 24];
            var caption = (sliderElem.attributes['data-caption']) ? sliderElem.attributes['data-caption'].value : "N/A";

            if (!entry.RestrictionsEnabled) {
                return "No restrictions for " + caption;
            } else {
                if (enabledTimes[0] == 0 && enabledTimes[1] == 24) {
                    return "No restrictions for " + caption;
                } else if (enabledTimes[0] == enabledTimes[1]) {
                    return "Internet restricted all day";
                } else {
                    // enabledTimes[0]
                    return "Internet allowed between " + UserRecord.timeOfDay(enabledTimes[0]) + " and " + UserRecord.timeOfDay(enabledTimes[1]);
                }
            }
        }
    }

    export abstract class BaseRecord {
        // ───────────────────────────────────────────────────
        //   :::::: C O N S T       V A R I A B L E S ::::::
        // ───────────────────────────────────────────────────
        MESSAGE_SAVING              = 'Saving record to server';
        MESSAGE_DELETING            = 'Deleting record from server';
        MESSAGE_ACTION_FAILED       = 'Error reported by the server during action.\n %ERROR_MSG% \nCheck console for more information.';

        TITLE_SAVING                = 'Saving Record';
        TITLE_DELETING              = 'Saving Record';
        TITLE_ACTION_FAILED         = 'Action Failed';

        BTN_LABEL_ADD_APP           = 'Add';
        BTN_LABEL_EDIT_APP          = 'Save';

        ERROR_MESSAGE_DELAY_TIME    = 5000;
        FADE_IN_DELAY_TIME          = 200;

        URL_ROUTE                   = 'api/admin/app';
        URL_APPGROUP_DATA           = 'api/admin/get_appgroup_data'

        // ───────────────────────────────────
        //   :::::: C A L L B A C K S ::::::
        // ───────────────────────────────────
        protected m_actionCompleteCallback  : ActionCompleteCallback;
        protected m_progressWait            : ProgressWait;

        public get ActionCompleteCallback(): ActionCompleteCallback {
            return this.m_actionCompleteCallback;
        }

        public set ActionCompleteCallback(value: ActionCompleteCallback) {
            this.m_actionCompleteCallback = value;
        }
        // ────────────────────────────────────────────────────
        //   :::::: M E M B E R     F U N C T I O N S ::::::
        // ────────────────────────────────────────────────────
        public abstract get RecordRoute(): string;

        protected abstract get ValidationOptions(): JQueryValidation.ValidationOptions;

        constructor() {
            this.m_progressWait = new ProgressWait();
        }

        public static CreateFromObject < RType extends BaseRecord > (type: {
            new(): RType;
        }, data: Object): RType {
            let inst = new type();
            inst.LoadFromObject(data);
            return inst;
        }

        public static GetRecordRoute < RType extends BaseRecord > (type: {
            new(): RType;
        }): string {
            let inst = new type();
            return inst.RecordRoute;
        }

        protected abstract LoadFromObject(value: Object): void;
        protected abstract LoadFromForm(): void;
        public abstract ToObject(): Object;

        public abstract StopEditing(): void;

        protected OnFormSubmitClicked(e: Event, newlyCreated: boolean): any {
            if (!e.defaultPrevented) {
                e.stopImmediatePropagation();
                e.stopPropagation();

                this.Save(newlyCreated);
            }

            return false;
        }


        protected SetFilterListHandler(inputSelector: string, listSelector: string) {
            let that = this;
            $(inputSelector).on("keyup", function() {
                that.FilterList(inputSelector, listSelector);
            });
        }

        protected FilterList(inputSelector: string, listSelector: string) {
            var value = ($(inputSelector).val() as string).toLowerCase();
            $(listSelector + " option").filter((index, element): boolean => {
                let visible = $(element).text().toLowerCase().indexOf(value) != -1;
                $(element).toggle(visible);
                return visible;
            });
        }

        public Save(newlyCreated: boolean = false): void {
            this.LoadFromForm();

            let dataObject = this.ToObject();
            this.m_progressWait.Show(this.TITLE_SAVING, this.MESSAGE_SAVING);

            let ajaxSettings: JQueryAjaxSettings = {
                method: newlyCreated == true ? "POST" : "PATCH",
                timeout: 60000,
                url: newlyCreated == true ? this.RecordRoute : this.RecordRoute + '/' + dataObject['id'],
                data: dataObject,
                success: (data: any): any => {
                    this.m_progressWait.Hide();
                    if (this.m_actionCompleteCallback != null) {
                        this.m_actionCompleteCallback(newlyCreated == true ? "Created" : "Updated");
                    }

                    return false;
                },
                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    this.m_progressWait.Show(this.TITLE_ACTION_FAILED, this.MESSAGE_ACTION_FAILED.replace('%ERROR_MSG', jqXHR.responseText));
                    setTimeout(() => {
                        this.m_progressWait.Hide();
                    }, 5000);
                }
            }

            $.ajax(ajaxSettings);
        }

        public Delete(): void {

            this.m_progressWait.Show(this.TITLE_DELETING, this.MESSAGE_DELETING);
            let dataObject = this.ToObject();
            let ajaxSettings: JQueryAjaxSettings = {
                method: "DELETE",
                timeout: 60000,
                url: this.RecordRoute + '/' + dataObject['id'],
                success: (data: any, textStatus: string, jqXHR: JQueryXHR): any => {
                    this.m_progressWait.Hide();
                    if (this.m_actionCompleteCallback != null) {
                        this.m_actionCompleteCallback("Deleted");
                    }

                    return false;
                },

                error: (jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any => {
                    console.log(jqXHR.responseText);
                    this.m_progressWait.Show(this.TITLE_ACTION_FAILED, this.MESSAGE_ACTION_FAILED.replace('%ERROR_MSG', jqXHR.responseText));
                    setTimeout(() => {
                        this.m_progressWait.Hide();
                    }, this.ERROR_MESSAGE_DELAY_TIME);
                }
            }

            $.ajax(ajaxSettings);
        }
    }
}