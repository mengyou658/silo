;
import React from 'react';
import BatchEditor from './Editor/BatchEditor';
import DataStore from './Editor/DataStore';
import Link from './Common/Link';

module.exports = React.createClass({

    getInitialState: function () {
        return {
            data: null,
            batches: new DataStore([])
        };
    },

    getDefaultProps: function() {
        return {
            siloBasePath: null,
            id: null
        };
    },

    componentDidMount: function () {
        this.props.cache.get('operation/'+this.props.id)
            .from(this.props.siloBasePath+"/inventory/operation/"+this.props.id)
            .onUpdate(function(value){
                this.setState({
                    data: value,
                    batches: new DataStore(value.batches)
                });
            }.bind(this))
            .refresh();
    },

    componentWillUnmount : function () {
        this.props.cache.cleanup('operation/'+this.props.id);
    },

    handleRollback: function () {
        $.post(
            this.props.siloBasePath+"/inventory/operation/"+this.props.id+"/rollback",
            {headers: {'Accept': 'application/json'}}
        )
            .done(function(data, textStatus, jqXHR){
                // @todo if jqXHR.status != 201 then do something
                this.props.cache.refresh('operation/'+this.props.id);
            }.bind(this));

    },

    render: function(){
        let data = this.state.data;
        return (
            <div>
                <h3><span className="glyphicon glyphicon-transfer" /> Operation {this.props.id}</h3>
                {!data && (<span>Loading</span>)}
                {data && <div>
                    <div className="pull-right">
                        {data.status.isRollbackable &&
                            <a className="btn btn-danger" onClick={this.handleRollback}>Rollback</a>
                        }
                        {data.rollback &&
                            <span>Rollbacked: <Link route="operation" code={data.rollback} /></span>
                        }
                    </div>
                        <b>Source:</b>&nbsp;{data.source ? <Link route="location" code={data.source} /> : "No source"}<br />
                        <b>Target:</b>&nbsp;{data.target ? <Link route="location" code={data.target} /> : "No target"}<br />
                        {data.location &&
                        (<span><b>Moved location:</b>&nbsp;<Link route="location" code={data.location} /><br /></span>)
                        }
                        {data.batches.length > 0 && (<div>
                                <b>Batches:</b>
                                <BatchEditor
                                    exportFilename={'operation-'+this.props.id+'-batches.csv'}
                                    batches={this.state.batches} />
                            </div>)
                        }
                </div>}
            </div>
        );
    }
});
