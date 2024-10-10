import {checkIfModelHasEmptyProperties, toObjectRequest} from "@/HelperFunctions";

export default class Assessment{
    toObjectRequest() {
        return toObjectRequest(this);
    }

    hasEmptyProperties() {
        return checkIfModelHasEmptyProperties(this);
    }

    static fromModel(model) {
        return new Assessment(model.id, model.evaluated_id, model.evaluator_id, model.role, model.pending, model.assessment_period_id, model.dependency_identifier);
    }

    static getPossibleRoles(){
        return [
            {name: 'jefe', value: 'jefe'},
            {name: 'par', value: 'par'},
            {name: 'cliente interno', value: 'cliente interno'},
            {name: 'cliente externo', value: 'cliente externo'},
        ];
    }

    constructor(id = null, evaluatedId = null, evaluatorId = null, role = '', pending = 1, assessmentPeriodId = null, dependencyIdentifier = null ) {
        this.id = id;
        this.evaluated_id = evaluatedId;
        this.evaluator_id = evaluatorId;
        this.role = role;
        this.pending = pending;
        this.assessmentPeriodId = assessmentPeriodId;
        this.dependencyIdentifier = dependencyIdentifier;

        this.dataStructure = {
            id: null,
            evaluated_id: null,
            evaluator_id: 'required',
            role: 'required',
            pending: null,
            assessmentPeriodId: null,
            dependencyIdentifier: null,
        }
    }
}
