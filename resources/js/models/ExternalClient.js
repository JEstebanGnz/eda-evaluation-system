import {checkIfModelHasEmptyProperties, toObjectRequest} from "@/HelperFunctions";

export default class ExternalClient {
    toObjectRequest() {
        return toObjectRequest(this);
    }

    hasEmptyProperties() {
        return checkIfModelHasEmptyProperties(this);
    }

    static fromModel(model) {
        return new ExternalClient(model.id, model.name, model.email, model.is_external_client);
    }

    constructor(id = null, name = '', email = "", isExternalClient = 1) {
        this.id = id;
        this.name = name;
        this.email = email;
        this.isExternalClient = isExternalClient;

        this.dataStructure = {
            id: null,
            name: 'required',
            email: 'email',
            isExternalClient: 'required',
        }
    }
}
