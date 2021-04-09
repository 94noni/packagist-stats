import { Controller } from 'stimulus';
import axios from "axios";

export default class extends Controller {
    static targets = ['info', 'error'];

    connect() {
        this.name = this.element.dataset.name;
        this.url = this.element.dataset.url;
    }

    info() {
        const that = this;
        const urlInfo = `https://api.github.com/repos/` + this.name;
        axios.get(urlInfo)
            .then(function (response) {
                let infoHTML = '<i class="fa fa-star"></i> Stars ' + response.data['stargazers_count'];
                infoHTML += '<br />';
                infoHTML += '<img src="'+response.data['organization']['avatar_url']+'" width="40" height="40" class="img-thumbnail" />';
                that.infoTarget.innerHTML = infoHTML;
            })
            .catch(function (error) {
                that.errorTarget.value = "Oops";
            })
        ;
    }
}
