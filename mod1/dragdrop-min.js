var sortable_currentItem;function sortable_unhideRecord(A,B){jumpToUrl(B)}function sortable_hideRecord(A,B){if(!sortable_removeHidden){return jumpToUrl(B)}while((typeof A.className=="undefined")||(A.className.search(/tpm-element(?!-)/)==-1)){A=A.parentNode}new Ajax.Request(B);new Effect.Fade(A,{duration:0.5,afterFinish:sortable_hideRecordCallBack})}function sortable_hideRecordCallBack(B){var A=B.element;while(A.lastChild){A.removeChild(A.lastChild)}}function sortable_unlinkRecordCallBack(C){var B=C.element;var A=B.parentNode;A.removeChild(B);sortable_update(A)}function sortable_unlinkRecord(A,C,B){new Ajax.Request("index.php?"+sortable_linkParameters+"&ajaxUnlinkRecord="+escape(A),{onSuccess:function(D){var E=Builder.build(D.responseText);$("tx_templavoila_mod1_sidebar-bar").setStyle({height:$("tx_templavoila_mod1_sidebar-bar").getHeight()+"px"});$("tx_templavoila_mod1_sidebar-bar").innerHTML=E.innerHTML;setTimeout(function(){sortable_unlinkRecordSidebarCallBack(B)},100)}});new Effect.Fade(C,{duration:0.5,afterFinish:sortable_unlinkRecordCallBack})}function sortable_unlinkRecordSidebarCallBack(D){var C=$("tx_templavoila_mod1_sidebar-bar").childElements();var B=0;for(var A=0;A<C.length;A++){B+=C[A].getHeight()}$("tx_templavoila_mod1_sidebar-bar").morph({height:B+"px"},{duration:0.1,afterFinish:function(){$("tx_templavoila_mod1_sidebar-bar").setStyle({height:"auto"});if(D&&$(D)){$(D).highlight()}}})}function sortable_updateItemButtons(D,A,B){var E=[],F=[];var C=escape(B+A);D.childElements().each(function(G){if(G.nodeName=="A"&&G.href){switch(G.className){case"tpm-new":G.href=G.href.replace(/&parentRecord=[^&]+/,"&parentRecord="+C);break;case"tpm-browse":if(G.rel){G.rel=G.rel.replace(/&destination=[^&]+/,"&destination="+C)}break;case"tpm-delete":G.href=G.href.replace(/&deleteRecord=[^&]+/,"&deleteRecord="+C);break;case"tpm-unlink":G.href=G.href.replace(/unlinkRecord\('[^']+'/,"unlinkRecord('"+C+"'");break;case"tpm-cut":case"tpm-copy":case"tpm-ref":G.href=G.href.replace(/CB\[el\]\[([^\]]+)\]=[^&]+/,"CB[el][$1]="+C);break;case"tpm-pasteAfter":case"tpm-pasteSubRef":G.href=G.href.replace(/&destination=[^&]+/,"&destination="+C);break;case"tpm-makeLocal":G.href=G.href.replace(/&makeLocalRecord=[^&]+/,"&makeLocalRecord="+C);break}}else{if(G.childElements()&&G.className!="tpm-subelement-table"){sortable_updateItemButtons(G,A,B)}}})}function sortable_update(C){var D=C.firstChild;var B=1;while(D!=null){if(!(typeof D.className=="undefined")&&D.className.search(/tpm-element(?!-)/)>-1){if(sortable_currentItem&&D.id==sortable_currentItem.id){var A=T3_TV_MOD1_BACKPATH+"ajax.php?ajaxID=tx_templavoila_mod1_ajax::moveRecord&source="+all_items[sortable_currentItem.id]+"&destination="+all_items[C.id]+(B-1);new Ajax.Request(A);sortable_currentItem=false}sortable_updateItemButtons(D,B,all_items[C.id]);all_items[D.id]=all_items[C.id]+B;B++}D=D.nextSibling}}function sortable_change(A){sortable_currentItem=A}function tv_createSortable(A,B){Position.includeScrollOffsets=true;Sortable.create(A,{tag:"div",ghosting:false,format:/(.*)/,handle:"sortable_handle",scroll:"typo3-docbody",scrollSpeed:30,dropOnEmpty:true,constraint:false,containment:B,onChange:sortable_change,onUpdate:sortable_update})};