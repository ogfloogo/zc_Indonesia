(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-account-quickstart"],{1833:function(e,t,o){"use strict";o.r(t);var n=o("21de"),r=o("1ba8");for(var a in r)"default"!==a&&function(e){o.d(t,e,(function(){return r[e]}))}(a);o("d6ae");var i,d=o("f0c5"),c=Object(d["a"])(r["default"],n["b"],n["c"],!1,null,"01e69001",null,!1,n["a"],i);t["default"]=c.exports},"1ba8":function(e,t,o){"use strict";o.r(t);var n=o("b808"),r=o.n(n);for(var a in n)"default"!==a&&function(e){o.d(t,e,(function(){return n[e]}))}(a);t["default"]=r.a},"21de":function(e,t,o){"use strict";var n;o.d(t,"b",(function(){return r})),o.d(t,"c",(function(){return a})),o.d(t,"a",(function(){return n}));var r=function(){var e=this,t=e.$createElement,o=e._self._c||t;return o("v-uni-view",{staticClass:"about"},[o("v-uni-view",{domProps:{innerHTML:e._s(e.content)}})],1)},a=[]},"9b11":function(e,t,o){var n=o("ef86");"string"===typeof n&&(n=[[e.i,n,""]]),n.locals&&(e.exports=n.locals);var r=o("4f06").default;r("3ff72bdc",n,!0,{sourceMap:!1,shadowMode:!1})},b808:function(e,t,o){"use strict";o("99af"),o("4d63"),o("ac1f"),o("25f0"),o("466d"),o("841c"),Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;o("26cb");var n={data:function(){return{}},onLoad:function(e){console.log(e.token)},onShow:function(){console.log(webUni)},computed:{getVersion:function(){var e=new RegExp("(^|&)type=([^&]*)(&|$)"),t=window.location.search.substr(1).match(e);return console.log(t),null!=t?unescape(t[2]):""},content:function(){return'<p>1. Login to get rewards</p><p>Users can get our APP through the Google download link. After the installation is complete, follow the prompts to register an account to get a 100-peso novice gift package. (IOS users can log in to Grouping directly through the web version)</p><p style="text-align:center">\n\t\t\t<iframe width="100%" height="'.concat(uni.upx2px(376.875),'" src="https://www.youtube.com/embed/L2j_u6q8qrc" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>\n\t\t\t<video class="edui-upload-video  vjs-default-skin      video-js" controls="" preload="none" width="100%" height="').concat(uni.upx2px(280/420*670),'" src="http://www.vgrouping.com/uploads/20221012/bdd4e6cffb003f2a03b7989d6c49e60d.mp4"><source src="http://www.vgrouping.com/uploads/20221012/bdd4e6cffb003f2a03b7989d6c49e60d.mp4" type="video/mp4"/></video></p><p>2. Participate in group buying:</p><p>At the same time, many people participate in the group, and one person will get the goods at a very low price during the same period. Users who have obtained the goods can choose to send the goods in kind, or they can choose to be recycled by the platform at a higher price. If the group does not get product users, you can get additional rebates.</p><p style="text-align:center"><video class="edui-upload-video  vjs-default-skin   video-js" controls="" preload="none" width="100%" height="').concat(uni.upx2px(280/420*670),'" src="http://www.vgrouping.com/uploads/20221012/b9e7115e7d8ad4728e5b0ac42a84a387.mp4"><source src="http://www.vgrouping.com/uploads/20221012/b9e7115e7d8ad4728e5b0ac42a84a387.mp4" type="video/mp4"/></video></p><p>3. Deposit and Withdrawal</p><p>Recharge: You can quickly increase the level and income</p><p>Withdrawal: No doubt, all wallet balances in Grouping support withdrawal</p><p style="text-align:center"><video class="edui-upload-video  vjs-default-skin      video-js" controls="" preload="none" width="100%" height="').concat(uni.upx2px(280/420*670),'" src="http://www.vgrouping.com/uploads/20221012/3f38475084d500183b5d65b35e71d7b4.mp4"><source src="http://www.vgrouping.com/uploads/20221012/3f38475084d500183b5d65b35e71d7b4.mp4" type="video/mp4"/></video></p><p style="text-align:center"><video class="edui-upload-video  vjs-default-skin     video-js" controls="" preload="none" width="100%" height="').concat(uni.upx2px(280/420*670),'" src="http://www.vgrouping.com/uploads/20221012/4ed8e08c2d82534fe6c5186d623300c1.mp4"><source src="http://www.vgrouping.com/uploads/20221012/4ed8e08c2d82534fe6c5186d623300c1.mp4" type="video/mp4"/></video></p><p>4. Invite and share</p><p>Invite to enjoy 3 great gifts</p><p style="text-align:center"><video class="edui-upload-video  vjs-default-skin    video-js" controls="" preload="none" width="100%" height="').concat(uni.upx2px(280/420*670),'" src="http://www.vgrouping.com/uploads/20221012/23c3f69e1de99db7df14ebd33ccb7668.mp4"><source src="http://www.vgrouping.com/uploads/20221012/23c3f69e1de99db7df14ebd33ccb7668.mp4" type="video/mp4"/></video></p><p><br/></p>')}},methods:{toInvite:function(e){webUni.navigateTo({url:"/pages/team/invite"})},handleBack:function(){webUni.navigateBack()}}};t.default=n},d6ae:function(e,t,o){"use strict";var n=o("9b11"),r=o.n(n);r.a},ef86:function(e,t,o){var n=o("24fb");t=n(!1),t.push([e.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */\r\n/* 主题配置 */[data-v-01e69001]:export{theme:#be01fd;theme_opa:#edf2ee;ttext:#be01fd\r\n  /* ... */}.about[data-v-01e69001]{overflow:hidden;min-height:100vh;background:#fff;display:flex;flex-direction:column;padding:%?40?%}[data-v-01e69001] .edui-upload-video{margin:%?30?% 0 0 0}',""]),e.exports=t}}]);