import {checkIfModelHasEmptyProperties, toObjectRequest} from "@/HelperFunctions";

export default class Commitment {
    toObjectRequest() {
        return toObjectRequest(this);
    }

    hasEmptyProperties() {
        return checkIfModelHasEmptyProperties(this);
    }

    static fromModel(model) {
        return new Commitment(model.id, model.user_id, model.training_id, model.due_date, model.done, model.done_date,  model.assessment_period_id);
    }

    constructor(id = null,  userId = null, trainingId = null,
                dueDate = '', done = false, doneDate = null, assessmentPeriodId = null) {
        this.id = id;
        this.user_id = userId;
        this.training_id = trainingId;
        this.due_date = dueDate;
        this.done = done;
        this.done_date = doneDate;
        this.assessment_period_id= assessmentPeriodId;

        this.dataStructure = {
            id: null,
            user_id: 'required',
            training_id: 'required',
            due_date: 'required',
            done: 'required',
            done_date: null,
            assessment_period_id: null
        }
    }
}
