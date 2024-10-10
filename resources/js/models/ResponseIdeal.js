import {checkIfModelHasEmptyProperties, toObjectRequest} from "@/HelperFunctions";

export default class ResponseIdeal {
    toObjectRequest() {
        return toObjectRequest(this);
    }

    hasEmptyProperties() {
        return checkIfModelHasEmptyProperties(this);
    }

    static fromModel(model) {
        return new ResponseIdeal(model.id, model.position_id, model.response, model.assessment_period_id);
    }

    createInitialResponseFromCompetencesArray(competences) {
        let response = [];
        competences.forEach((competence,index) => {
            response.push({
                id:index,
                name: competence.name,
                value: null,
                position: competence.position
            })
        });
        return response
    }

    constructor(id = null, position_id = null, response = null, assessment_period_id = null) {
        this.id = id;
        this.position_id = position_id;
        this.response = response;
        this.assessment_period_id = assessment_period_id

        this.dataStructure = {
            id: null,
            position_id: 'required',
            response: 'required',
            assessment_period_id: null
        }
    }
}
