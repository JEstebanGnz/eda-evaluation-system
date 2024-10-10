import {checkIfModelHasEmptyProperties, toObjectRequest} from "@/HelperFunctions";

export default class Training{
    toObjectRequest() {
        return toObjectRequest(this);
    }

    hasEmptyProperties() {
        return checkIfModelHasEmptyProperties(this);
    }

    static fromModel(model) {
        return new Training(model.id, model.name);
    }

    constructor(id = null, name = '') {
        this.id = id;
        this.name = name;

        this.dataStructure = {
            id: null,
            name: 'required',
        }
    }
}
