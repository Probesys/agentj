import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["times", "weekends", "mode", "origin", "picker", "slots", "slotTemplate", "defaults", "domainSchedule"];
    static values = {
        domainWeekends: Boolean,
        inheritedLabel: String,
        customLabel: String,
    };

    connect() {
        this.refreshOrigin();
        // Keep the text field as a fallback when JavaScript is unavailable,
        // or when displaying an invalid submitted value.
        const times = this.timesTarget.value.split(",").map(time => time.trim()).filter(Boolean);
        if (times.some(time => !/^([01]\d|2[0-3]):[0-5]\d$/.test(time))) return;
        this.timesTarget.type = "hidden";
        this.pickerTarget.hidden = false;
        this.slotsTarget.replaceChildren();
        (times.length ? times : [""]).forEach(time => this.createSlot(time));
        this.refreshDefaults();
    }

    createSlot(value = "") {
        const slot = this.slotTemplateTarget.content.firstElementChild.cloneNode(true);
        slot.querySelector("input").value = value;
        this.slotsTarget.append(slot);
        return slot;
    }

    addTime() {
        this.createSlot().querySelector("input").focus();
    }

    removeTime(event) {
        event.currentTarget.closest(".report-preferences__slot").remove();
        if (!this.slotsTarget.children.length) this.createSlot();
        this.changeTime();
        this.slotsTarget.querySelector("input").focus();
    }

    changeTime() {
        this.timesTarget.value = Array.from(this.slotsTarget.querySelectorAll("input"))
            .map(input => input.value).filter(Boolean).join(", ");
    }

    chooseWeekends() {
        if (!this.hasModeTarget) return;
        this.modeTarget.value = "custom";
        this.refreshOrigin();
    }

    changeWeekendMode() {
        if (this.modeTarget.value === "inherit") {
            this.weekendsTarget.checked = this.domainWeekendsValue;
        }
        this.refreshOrigin();
    }

    toggleDefaults() {
        if (this.defaultsTarget.checked) {
            this.timesTarget.value = "";
            this.slotsTarget.replaceChildren();
            this.createSlot();
        }
        // The toggle only controls sending times. Weekend choices remain independent.
        this.refreshDefaults();
    }

    refreshDefaults() {
        if (!this.hasDefaultsTarget) return;
        this.pickerTarget.hidden = this.defaultsTarget.checked;
        this.domainScheduleTarget.hidden = !this.defaultsTarget.checked;
    }

    refreshOrigin() {
        if (!this.hasOriginTarget) return;
        this.originTarget.textContent = this.modeTarget.value === "custom"
            ? this.customLabelValue
            : this.inheritedLabelValue;
    }
}
