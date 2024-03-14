import cloneDeep from 'lodash/cloneDeep'

export default function repairConfig(
  config,
  camp,
  availableLocales,
  componentRepairers,
  defaultContents
) {
  const configClone = config ? cloneDeep(config) : {}
  if (!availableLocales.includes(configClone.language)) configClone.language = 'en'
  if (!configClone.documentName) configClone.documentName = camp.name
  if (configClone.camp !== camp._meta.self) configClone.camp = camp._meta.self
  if (typeof configClone.contents?.map !== 'function') {
    configClone.contents = defaultContents
  }
  configClone.contents = configClone.contents
    .map((content) => {
      if (!content.type || !(content.type in componentRepairers)) return null
      const componentRepairer = componentRepairers[content.type]
      if (typeof componentRepairer !== 'function') return content
      return componentRepairer(content, camp)
    })
    .filter((component) => component)

  return configClone
}
