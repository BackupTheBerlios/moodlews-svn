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
<xsl:template match="users">
	<html><body>
	<table>
		<tr>
			<td><h3 align="left">Nome</h3></td>
			<td><h3 align="left">Cognome</h3></td>
			<td><h3 align="left">Matricola</h3></td>
			<td><h3 align="left">E-Mail</h3></td>
		</tr>	
	<xsl:apply-templates/>
	</table>
	</body></html>
</xsl:template>
<xsl:template match="data">
	<xsl:apply-templates/>
</xsl:template>
<xsl:template match="user">
	<tr>
		<xsl:apply-templates select="firstname"/>
		<xsl:apply-templates select="lastname"/>
		<xsl:apply-templates select="moodle_oriented/idnumber"/>
		<xsl:apply-templates select="email"/>
	</tr>
</xsl:template>
<xsl:template match="firstname|lastname|idnumber|email">
	<td>
		<xsl:value-of select="."/>
	</td>
</xsl:template>
</xsl:stylesheet>
