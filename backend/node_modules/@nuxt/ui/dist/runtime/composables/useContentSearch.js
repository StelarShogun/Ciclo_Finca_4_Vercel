import { ref } from "vue";
import { createSharedComposable } from "@vueuse/core";
import { useAppConfig } from "#imports";
function _useContentSearch() {
  const open = ref(false);
  const appConfig = useAppConfig();
  function mapFile(file, link, parent) {
    const prefix = [...new Set([parent?.title, ...file.titles].filter(Boolean))];
    return {
      prefix: prefix?.length ? prefix.join(" > ") + " >" : void 0,
      label: file.id === link.path ? link.title : file.title,
      suffix: file.content.replaceAll("<", "&lt;").replaceAll(">", "&gt;"),
      to: file.id,
      icon: link.icon || parent?.icon || (file.level > 1 ? appConfig.ui.icons.hash : appConfig.ui.icons.file),
      level: file.level
    };
  }
  function mapNavigationItems(children, files, parent) {
    const filesByPath = /* @__PURE__ */ new Map();
    for (const file of files || []) {
      const basePath = file.id.split("#")[0] || file.id;
      let bucket = filesByPath.get(basePath);
      if (!bucket) {
        bucket = [];
        filesByPath.set(basePath, bucket);
      }
      bucket.push(file);
    }
    function visit(nodes, nodeParent) {
      return nodes.flatMap((link) => {
        if (link.children?.length) {
          return visit(link.children, link);
        }
        const matched = link.path ? filesByPath.get(link.path) : void 0;
        return matched?.map((file) => mapFile(file, link, nodeParent)) || [];
      });
    }
    return visit(children, parent);
  }
  function postFilter(query, items) {
    if (!query) {
      return items?.filter((item) => item.level === 1);
    }
    return items;
  }
  return {
    open,
    mapFile,
    mapNavigationItems,
    postFilter
  };
}
export const useContentSearch = /* @__PURE__ */ createSharedComposable(_useContentSearch);
