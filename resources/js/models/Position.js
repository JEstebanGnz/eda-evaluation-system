import {checkIfModelHasEmptyProperties, toObjectRequest} from "@/HelperFunctions";

export default class Position {
    toObjectRequest() {
        return toObjectRequest(this);
    }

    hasEmptyProperties() {
        return checkIfModelHasEmptyProperties(this);
    }

    static fromModel(model) {
        return new Position(model.id, model.name);
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
