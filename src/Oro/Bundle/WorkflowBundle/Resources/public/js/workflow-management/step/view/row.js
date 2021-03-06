/* global define */
define(['underscore', 'backbone', 'oroworkflow/js/workflow-management/transition/view/list-short'],
function(_, Backbone, TransitionsShortListView) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oroworkflow/js/workflow-management/step/view/row
     * @class   oro.WorkflowManagement.StepRowView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        tagName: 'tr',

        events: {
            'click .edit-step': 'triggerEditStep',
            'click .delete-step': 'triggerRemoveStep',
            'click .clone-step': 'triggerCloneStep',
            'click .add-step-transition': 'triggerAddStepTransition'
        },

        options: {
            workflow: null,
            template: null
        },

        initialize: function() {
            var template = this.options.template || $('#step-row-template').html();
            this.template = _.template(template);
            this.transitionsListView = null;
            this.listenTo(this.model, 'destroy', this.remove);
        },

        triggerEditStep: function(e) {
            e.preventDefault();
            this.options.workflow.trigger('requestEditStep', this.model);
        },

        triggerRemoveStep: function(e) {
            e.preventDefault();
            this.options.workflow.trigger('requestRemoveStep', this.model);
        },

        triggerCloneStep: function(e) {
            e.preventDefault();
            this.options.workflow.trigger('requestCloneStep', this.model);
        },

        triggerAddStepTransition: function(e) {
            e.preventDefault();
            this.options.workflow.trigger('requestAddTransition', this.model);
        },

        remove: function() {
            if (this.transitionsListView) {
                this.transitionsListView.remove();
            }
            Backbone.View.prototype.remove.call(this);
        },

        render: function() {
            this.transitionsListView = new TransitionsShortListView({
                'collection': this.model.getAllowedTransitions(this.options.workflow),
                'workflow': this.options.workflow,
                stepFrom: this.model
            });
            var rowHtml = $(this.template(this.model.toJSON()));
            var transitionsListEl = this.transitionsListView.render().$el;
            rowHtml.filter('.step-transitions').append(transitionsListEl);
            this.$el.append(rowHtml);

            return this;
        }
    });
});
