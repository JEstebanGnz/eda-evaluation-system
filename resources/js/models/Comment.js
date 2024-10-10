import {checkIfModelHasEmptyProperties, toObjectRequest} from "@/HelperFunctions";

export default class Comment {
    toObjectRequest() {
        return toObjectRequest(this);
    }

    hasEmptyProperties() {
        return checkIfModelHasEmptyProperties(this);
    }

    static fromModel(model) {
        return new Comment(model.id, model.text, model.commitment_id, model.posted_by);
    }

    constructor(id = null,  text = null, commitmentId = null, postedBy = null) {
        this.id = id;
        this.text = text;
        this.commitment_id = commitmentId;
        this.posted_by= postedBy;

        this.dataStructure = {
            id: null,
            text: 'required',
            commitment_id: 'null',
            posted_by:'null'
        }
    }
}
