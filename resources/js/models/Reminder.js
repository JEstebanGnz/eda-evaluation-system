import {checkIfModelHasEmptyProperties, toObjectRequest} from "@/HelperFunctions";

export default class Reminder {
    toObjectRequest() {
        return toObjectRequest(this);
    }

    hasEmptyProperties() {
        return checkIfModelHasEmptyProperties(this);
    }

    static fromModel(model) {
        return new Reminder(model.id, model.reminder_type, model.send_before, model.days);
    }

    constructor(id = null,  reminderType = '', sendBefore = '', days = null) {
        this.id = id;
        this.reminder_type = reminderType;
        this.send_before = sendBefore;
        this.days= days;

        this.dataStructure = {
            id: null,
            reminder_type: 'required',
            send_before: 'required',
            days:'required'
        }
    }
}
