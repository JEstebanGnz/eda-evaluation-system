import {checkIfModelHasEmptyProperties, toObjectRequest} from "@/HelperFunctions";

export default class Form {

    toObjectRequest() {
        return toObjectRequest(this);
    }

    hasEmptyProperties() {
        return checkIfModelHasEmptyProperties(this);
    }

    static copy(form) {
        return new Form(form.id, form.name, form.description, form.assessmentPeriodId, form.dependencyRole, form.position);
    }

    static createFormsFromArray(models) {
        let forms = []
        models.forEach(function (model) {
            forms.push(Form.fromModel(model));
        })
        return forms;
    }

    static fromModel(model) {
        return new Form(model.id, model.name, model.description, model.assessment_period_id, model.dependency_role, model.position);
    }

    static getPossibleRoles() {
        return [
            {name: 'Todos', value: null},
            {name: 'jefe', value: 'jefe'},
            {name: 'par', value: 'par'},
            {name: 'autoevaluación',value: 'autoevaluación'},
            {name: 'cliente interno',value: 'cliente interno'},
            {name: 'cliente externo',value: 'cliente externo'}
        ];
    }

    constructor(id = null, name = '', description = '', assessmentPeriodId = null, dependencyRole = null, position = null) {
        this.id = id;
        this.name = name;
        this.description = description;
        this.assessmentPeriodId = assessmentPeriodId;
        this.dependencyRole = dependencyRole;
        this.position = position;

        this.dataStructure = {
            id: null,
            name: 'required',
            description: null,
            assessmentPeriod: null,
            academicPeriodId: null,
            dependencyRole: null,
            position: null
        }
    }
}
