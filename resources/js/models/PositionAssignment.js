import {checkIfModelHasEmptyProperties, toObjectRequest} from "@/HelperFunctions";

export default class PositionAssignment {
    toObjectRequest() {
        return toObjectRequest(this);
    }

    hasEmptyProperties() {
        return checkIfModelHasEmptyProperties(this);
    }

    static fromModel(model) {
        return new PositionAssignment(model.id, model.job_title, model.position_id, model.assessment_period_id);
    }

    constructor(id = null, job_title = '', position_id ='', assessment_period_id = '') {
        this.id = id;
        this.job_title = job_title;
        this.position_id = position_id;
        this.assessment_period_id = assessment_period_id;

        this.dataStructure = {
            id: null,
            job_title: 'required',
            position_id: 'required',
            assessment_period_id: 'required'
        }
    }
}
