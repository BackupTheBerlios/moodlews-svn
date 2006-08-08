<xsl:stylesheet
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	version="1.0">
<!--
Copyright 2005 Simone Gammeri, Francesco Di Cerbo, LIPS Lab University of Genova, ITALY

This file is part of MoodleSoapPlugin.

MoodleSoapPlugin is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

MoodleSoapPlugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with MoodleSoapPlugin; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
--!>
<xsl:template match="pianostudi">
	<courses>
		<xsl:for-each select="insegnamento[anno_di_corso=3]">
			<course>
				<password>inginfo</password>
				<fullname>
					<xsl:value-of select="@titolo"/>
				</fullname>
				<category></category>
				<summary>
					<xsl:value-of select="descr_periodo"/>
				</summary>
				<shortname>
					<xsl:value-of select="codice"/>
				</shortname>
				<idnumber>
					<xsl:value-of select="codice"/>
				</idnumber>
				<format>weeks</format>
				<showgrades>1</showgrades> 
				<modinfo></modinfo>
				<blockinfo></blockinfo>
				<newsitems>5</newsitems>
				<teacher>Professore</teacher>
				<teachers>Insegnanti</teachers>
				<student>Studente</student>
				<students>Studenti</students>
				<guest>0</guest>
				<startdate></startdate>
				<enrolperiod></enrolperiod>
				<numsections>10</numsections>
				<marker>0</marker>
				<maxbytes>2097152</maxbytes>
				<showreports>1</showreports>
				<visible>1</visible>
				<hiddensections>0</hiddensections>
				<groupmode>0</groupmode>
				<groupmodeforce>0</groupmodeforce>
				<lang></lang>
				<cost></cost>
				<timecreated></timecreated>
				<timemodified></timemodified>
			</course>
		</xsl:for-each>
	</courses>
</xsl:template>
</xsl:stylesheet>
