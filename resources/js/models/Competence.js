import {checkIfModelHasEmptyProperties, toObjectRequest} from "@/HelperFunctions";

export default class Competence {
    toObjectRequest() {
        return toObjectRequest(this);
    }

    hasEmptyProperties() {
        return checkIfModelHasEmptyProperties(this);
    }

    static fromModel(model) {
        return new Competence(model.id, model.name, model.position, model.assessment_period_id);
    }

    constructor(id = null, name = '', position = null, assessment_period_id = null) {
        this.id = id;
        this.name = name;
        this.position = position;
        this.assessment_period_id = null;

        this.dataStructure = {
            id: null,
            name: 'required',
            position: 'required',
            assessment_period_id: null,
        }
    }
}
